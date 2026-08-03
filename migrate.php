<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$command = $argv[1] ?? 'migrate';
$allowedCommands = ['migrate', 'status'];
if (!in_array($command, $allowedCommands, true)) {
    fwrite(STDERR, "Usage : php migrate.php [migrate|status]\n");
    exit(1);
}

$migrationsDirectory = __DIR__ . '/migrations';
if (!is_dir($migrationsDirectory)) {
    fwrite(STDERR, "Dossier de migrations introuvable : {$migrationsDirectory}\n");
    exit(1);
}

$migrationFiles = array_values(array_filter(
    scandir($migrationsDirectory) ?: [],
    static fn(string $file): bool => preg_match('/^\d{3}_[a-z0-9_]+\.sql$/', $file) === 1
));
sort($migrationFiles, SORT_STRING);

try {
    $database = Database::getInstance();
    initialiseMigrationRepository($database);
    $applied = loadAppliedMigrations($database);
    backfillAndVerifyChecksums($database, $migrationsDirectory, $migrationFiles, $applied);
    $applied = loadAppliedMigrations($database);

    if ($command === 'status') {
        printStatus($migrationFiles, $applied);
        exit(0);
    }

    $pending = array_values(array_filter(
        $migrationFiles,
        static fn(string $file): bool => !isset($applied[$file])
    ));

    if ($pending === []) {
        echo "Aucune nouvelle migration à appliquer.\n";
        exit(0);
    }

    $batch = (int) $database->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations')->fetchColumn();
    foreach ($pending as $file) {
        applyMigration($database, $migrationsDirectory, $file, $batch);
    }

    echo count($pending) . " migration(s) appliquée(s), batch {$batch}.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Échec des migrations : {$exception->getMessage()}\n");
    exit(1);
}

function initialiseMigrationRepository(PDO $database): void
{
    $database->exec(
        'CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            checksum CHAR(64) NULL,
            batch INT NOT NULL DEFAULT 1,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $columns = $database->query('SHOW COLUMNS FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('checksum', $columns, true)) {
        $database->exec('ALTER TABLE migrations ADD COLUMN checksum CHAR(64) NULL AFTER migration_name');
    }
    if (!in_array('batch', $columns, true)) {
        $database->exec('ALTER TABLE migrations ADD COLUMN batch INT NOT NULL DEFAULT 1 AFTER checksum');
    }
}

function loadAppliedMigrations(PDO $database): array
{
    $rows = $database->query(
        'SELECT migration_name, checksum, batch, applied_at FROM migrations ORDER BY migration_name'
    )->fetchAll();

    $applied = [];
    foreach ($rows as $row) {
        $applied[$row['migration_name']] = $row;
    }
    return $applied;
}

function backfillAndVerifyChecksums(
    PDO $database,
    string $directory,
    array $files,
    array $applied
): void {
    $update = $database->prepare('UPDATE migrations SET checksum = ? WHERE migration_name = ?');
    foreach ($files as $file) {
        if (!isset($applied[$file])) {
            continue;
        }
        $checksum = hash_file('sha256', $directory . '/' . $file);
        if ($checksum === false) {
            throw new RuntimeException("Impossible de calculer l'empreinte de {$file}.");
        }
        $storedChecksum = $applied[$file]['checksum'];
        if ($storedChecksum === null || $storedChecksum === '') {
            $update->execute([$checksum, $file]);
            continue;
        }
        if (!hash_equals($storedChecksum, $checksum)) {
            throw new RuntimeException(
                "La migration déjà appliquée {$file} a été modifiée. Créez une nouvelle migration."
            );
        }
    }
}

function applyMigration(PDO $database, string $directory, string $file, int $batch): void
{
    $path = $directory . '/' . $file;
    $sql = file_get_contents($path);
    $checksum = hash_file('sha256', $path);
    if ($sql === false || $checksum === false) {
        throw new RuntimeException("Impossible de lire la migration {$file}.");
    }

    echo "Application : {$file}\n";
    $database->exec($sql);
    $statement = $database->prepare(
        'INSERT INTO migrations (migration_name, checksum, batch) VALUES (?, ?, ?)'
    );
    $statement->execute([$file, $checksum, $batch]);
}

function printStatus(array $files, array $applied): void
{
    echo str_pad('Migration', 52) . str_pad('État', 12) . "Batch\n";
    echo str_repeat('-', 72) . "\n";
    foreach ($files as $file) {
        $isApplied = isset($applied[$file]);
        echo str_pad($file, 52)
            . str_pad($isApplied ? 'appliquée' : 'en attente', 12)
            . ($isApplied ? (string) $applied[$file]['batch'] : '-')
            . "\n";
    }

    $missingFiles = array_diff(array_keys($applied), $files);
    foreach ($missingFiles as $file) {
        echo str_pad($file, 52) . str_pad('fichier absent', 12) . $applied[$file]['batch'] . "\n";
    }
}
