<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use Dotenv\Dotenv;

try {
    // Chargement de l'environnement
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
    }

    $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');
    error_reporting(E_ALL);

    // Démarrage de la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Chargement de la langue
    $lang = $_SESSION['lang'] ?? 'fr';
    \App\Core\Translator::load($lang);

    if (!function_exists('__')) {
        function __(string $key, array $params = []): string {
            return \App\Core\Translator::get($key, $params);
        }
    }

    // Chargement des routes
    $router = new Router();
    require_once __DIR__ . '/../routes/web.php';

    // Résolution de la route
    $router->resolve();
} catch (\Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo "<h1>Erreur système</h1>";
    echo $debug ? '<pre>' . htmlspecialchars((string) $e) . '</pre>' : '<p>Une erreur interne est survenue.</p>';
}
