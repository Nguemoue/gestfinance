<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Services\PdfService;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Services\AccessService;

class FicheController extends Controller
{
    private PdfService $pdfService;

    public function __construct()
    {
        AuthMiddleware::handle();
        $this->pdfService = new PdfService();
    }

    public function generate(int $id): void
    {
        $db = Database::getInstance();
        
        // 1. Récupérer la demande
        $stmt = $db->prepare("SELECT d.*, u.nom, u.prenom, s.libelle as service_nom 
                             FROM demandes d 
                             JOIN users u ON d.user_id = u.id 
                             JOIN services s ON d.service_id = s.id 
                             WHERE d.id = ?");
        $stmt->execute([$id]);
        $demande = $stmt->fetch();

        if (!$demande) {
            die("Demande non trouvée.");
        }
        if (!AccessService::canViewDemande($demande)) {
            http_response_code(403);
            die("Accès non autorisé.");
        }

        // 2. Récupérer les validations
        $stmt = $db->prepare("SELECT v.*, u.nom, u.prenom 
                             FROM validations v 
                             JOIN users u ON v.validateur_id = u.id 
                             WHERE v.demande_id = ? 
                             ORDER BY v.created_at ASC");
        $stmt->execute([$id]);
        $validations = $stmt->fetchAll();

        // 3. Préparer le HTML
        ob_start();
        include __DIR__ . '/../../../views/fiche/template.php';
        $html = ob_get_clean();

        // 4. Générer le PDF
        $this->pdfService->generate($html, "Fiche_Besoin_{$id}.pdf");
    }
}
