<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Enums\CategorieUtilisateur;
use App\Enums\ServiceTypeEnum;


echo "--- Démarrage du seeding ---\n";

try {
    $db = Database::getInstance();

    // 1. Insertion des Services via l'Enum ServiceType
    echo "Insertion des services...\n";
    $stmtService = $db->prepare("INSERT IGNORE INTO services (libelle, code, description) VALUES (?, ?, ?)");
    
    foreach (ServiceTypeEnum::cases() as $service) {
        $stmtService->execute([
            $service->label(),
            $service->value,
            $service->description()
        ]);
    }
    
    $infServiceId = $db->query("SELECT id FROM services WHERE code = '" . ServiceTypeEnum::INF->value . "'")->fetchColumn();
    $dgServiceId = $db->query("SELECT id FROM services WHERE code = '" . ServiceTypeEnum::DG->value . "'")->fetchColumn();

    // 2. Insertion des rôles depuis l'unique enum d'autorisation
    echo "Insertion des rôles...\n";
    $stmtRole = $db->prepare("INSERT IGNORE INTO roles (libelle, code, description) VALUES (?, ?, ?)");
    
    foreach (CategorieUtilisateur::cases() as $role) {
        $stmtRole->execute([
            $role->label(),
            $role->value,
            'Rôle canonique GestFinance'
        ]);
    }
    
    $roleIds = $db->query("SELECT code, id FROM roles")->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. Préparation de l'insertion des utilisateurs
    $insertUserSql = "INSERT INTO users (nom, prenom, email, password_hash, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtInsertUser = $db->prepare($insertUserSql);
    
    $checkUserStmt = $db->prepare("SELECT id FROM users WHERE email = ?");

    $comptaServiceId = $db->query("SELECT id FROM services WHERE code = '" . ServiceTypeEnum::COMPTA->value . "'")->fetchColumn();

    // Insertion de l'utilisateur super admin

    echo "Veirification de l'utilisateur Super Admin";
    $superAdminEmail = 'superadmin@gestfinance.com';
    $password = password_hash("admin123",PASSWORD_DEFAULT);

    $checkUserStmt->execute([$superAdminEmail]);
    if (!$checkUserStmt->fetch()) {
        $stmtInsertUser->execute([
            'SA', 
            'Prenom', 
            $superAdminEmail, 
            $password, 
            $roleIds[CategorieUtilisateur::SUPER_ADMIN->value],
            1
        ]);
        echo "✅ Compte Super Admin créé : $superAdminEmail / admin123\n";
    } else {
        echo "ℹ️ L'utilisateur Super Admin existe déjà.\n";
    }
    
    // 4. Insertion de l'utilisateur DG par défaut
    echo "Vérification de l'utilisateur DG...\n";
    $dgEmail = 'dg@gestfinance.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);

    $checkUserStmt->execute([$dgEmail]);
    if (!$checkUserStmt->fetch()) {
        $stmtInsertUser->execute([
            'GÉNÉRAL', 
            'Directeur', 
            $dgEmail, 
            $password, 
            $roleIds[CategorieUtilisateur::DG->value],
            1
        ]);
        echo "✅ Compte DG créé : $dgEmail / admin123\n";
    } else {
        echo "ℹ️ L'utilisateur DG existe déjà.\n";
    }

    // 5. Insertion du Responsable Comptabilité (RA)
    echo "Vérification du Responsable Comptabilité...\n";
    $raEmail = 'ra@gestfinance.com';
    
    $checkUserStmt->execute([$raEmail]);
    if (!$checkUserStmt->fetch()) {
        $stmtInsertUser->execute([
            'COMPTABILITÉ', 
            'Responsable', 
            $raEmail, 
            $password, 
            $roleIds[CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value],
            1
        ]);
        echo "✅ Compte Responsable Comptabilité créé : $raEmail / admin123\n";
    } else {
        echo "ℹ️ L'utilisateur Responsable Comptabilité existe déjà.\n";
    }

    // Deux responsables administratifs actifs sont prévus par le workflow.
    $ra2Email = 'ra2@gestfinance.com';
    $checkUserStmt->execute([$ra2Email]);
    if (!$checkUserStmt->fetch()) {
        $stmtInsertUser->execute([
            'ADMINISTRATION',
            'Responsable 2',
            $ra2Email,
            $password,
            $roleIds[CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value],
            1
        ]);
        echo "✅ Responsable Administratif Adjoint créé : $ra2Email / admin123\n";
    }

    // 6. Insertion d'un Agent de test
    echo "Vérification de l'agent de test...\n";
    $agentEmail = 'agent@gestfinance.com';
    
    $checkUserStmt->execute([$agentEmail]);
    if (!$checkUserStmt->fetch()) {
        $stmtInsertUser->execute([
            'TEST', 
            'Agent', 
            $agentEmail, 
            $password, 
            $roleIds[CategorieUtilisateur::AGENT->value],
            1
        ]);
        echo "✅ Compte Agent créé : $agentEmail / admin123\n";
    } else {
        echo "ℹ️ L'utilisateur agent existe déjà.\n";
    }

    $assignPrimaryService = $db->prepare(
        "INSERT INTO user_services (user_id, service_id, is_primary, is_responsable)
         SELECT id, ?, 1, 0 FROM users WHERE email = ?
         ON DUPLICATE KEY UPDATE is_primary = 1"
    );
    foreach ([
        $superAdminEmail => $dgServiceId,
        $dgEmail => $dgServiceId,
        $raEmail => $comptaServiceId,
        $ra2Email => $comptaServiceId,
        $agentEmail => $infServiceId,
    ] as $email => $serviceId) {
        $assignPrimaryService->execute([$serviceId, $email]);
    }

    echo "--- Seeding terminé ---\n";
} catch (Exception $e) {
    echo "❌ Erreur lors du seeding : " . $e->getMessage() . "\n";
    exit(1);
}
