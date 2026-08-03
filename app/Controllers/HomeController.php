<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Models\Demande;
use App\Middleware\AuthMiddleware;

class HomeController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::handle();
        
        $space = AuthHelper::getSpace();
        $userId = AuthHelper::getUserId();
        
        $demandeModel = new Demande();
        $data = [
            'title' => ($space === 'admin' ? 'Dashboard Administrateur' : 'Mon Espace Utilisateur'),
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Dashboard', 'url' => '/dashboard']
            ]
        ];

        // 1. Statistiques Personnelles (Toujours affichées pour l'utilisateur)
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'brouillon' THEN 1 ELSE 0 END) as brouillons,
                SUM(CASE WHEN statut IN ('soumis', 'valide_directeur', 'valide_dg') THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN statut = 'mis_a_disposition' THEN 1 ELSE 0 END) as finalisees,
                SUM(CASE WHEN statut = 'rejete' THEN 1 ELSE 0 END) as rejetees
            FROM demandes WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $data['user_stats'] = $stmt->fetch();

        // 2. Statistiques Globales (DG et RA uniquement dans l'espace Admin)
        if ($space === 'admin' && (AuthHelper::isDG() || AuthHelper::isRA())) {
            $data['global_stats'] = $demandeModel->getGlobalStats();
        }

        // 3. Statistiques de Service (Responsable uniquement)
        if (AuthHelper::isDirector()) {
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN statut = 'soumis' THEN 1 ELSE 0 END) as en_attente_validation
                FROM demandes d
                JOIN user_services us ON us.service_id = d.service_id
                WHERE us.user_id = ? AND us.is_responsable = 1
            ");
            $stmt->execute([$userId]);
            $data['service_stats'] = $stmt->fetch();
        }

        $this->render('dashboard', $data);
    }
}
