<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Services\ValidationService;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Enums\CategorieUtilisateur;

class ValidationController extends Controller
{
    private ValidationService $validationService;

    public function __construct()
    {
        AuthMiddleware::handle();
        $this->validationService = new ValidationService();
    }

    public function index(): void
    {
        $userCat = \App\Core\AuthHelper::getCategory();
        if ($userCat === CategorieUtilisateur::AGENT->value) {
            $this->redirect('/');
        }

        $db = \App\Core\Database::getInstance();
        $userId = \App\Core\AuthHelper::getUserId();

        // Demandes en attente de validation (selon rôle)
        if ($userCat === CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value) {
            $query = "SELECT d.*, u.nom, u.prenom 
                      FROM demandes d 
                      JOIN users u ON d.user_id = u.id 
                      JOIN services s ON d.service_id = s.id
                      WHERE d.statut = :statut AND s.responsable_id = :userId";
            $stmt = $db->prepare($query);
            $stmt->execute([
                'statut' => \App\Enums\StatutDemande::SOUMIS->value,
                'userId' => $userId
            ]);
            $demandes = $stmt->fetchAll();
        } elseif ($userCat === CategorieUtilisateur::DG->value) {
            $query = "SELECT d.*, u.nom, u.prenom 
                      FROM demandes d 
                      JOIN users u ON d.user_id = u.id 
                      WHERE d.statut = :statut";
            $stmt = $db->prepare($query);
            $stmt->execute(['statut' => \App\Enums\StatutDemande::VALIDE_DIRECTEUR->value]);
            $demandes = $stmt->fetchAll();
        } elseif ($userCat === CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value) {
            $query = "SELECT d.*, u.nom, u.prenom 
                      FROM demandes d 
                      JOIN users u ON d.user_id = u.id 
                      WHERE d.statut = :statut";
            $stmt = $db->prepare($query);
            $stmt->execute(['statut' => \App\Enums\StatutDemande::VALIDE_DG->value]);
            $demandes = $stmt->fetchAll();
        } else {
            $demandes = [];
        }

        // Historique des validations effectuées
        $stmt = $db->prepare("
            SELECT d.*, u.nom, u.prenom 
            FROM demandes d 
            JOIN users u ON d.user_id = u.id 
            WHERE d.id IN (
                SELECT demande_id FROM validations WHERE validateur_id = ? AND action = 'validation'
            )
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$userId]);
        $demandesPassees = $stmt->fetchAll();

        // Historique des rejets
        $stmt = $db->prepare("
            SELECT d.*, u.nom, u.prenom 
            FROM demandes d 
            JOIN users u ON d.user_id = u.id 
            WHERE d.id IN (
                SELECT demande_id FROM validations WHERE validateur_id = ? AND action = 'rejet'
            )
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$userId]);
        $demandesRejetees = $stmt->fetchAll();

        // Pour RA seulement : demandes mis_a_disposition divisées par is_justified
        $demandesAJustifier = [];
        $demandesJustifiees = [];
        if ($userCat === CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value) {
            // À justifier : mis_a_disposition et pas encore justifiées
            $stmt = $db->prepare("
                SELECT d.*, u.nom, u.prenom 
                FROM demandes d 
                JOIN users u ON d.user_id = u.id 
                WHERE d.statut = ? AND d.is_justified = 0
                ORDER BY d.updated_at DESC
            ");
            $stmt->execute([\App\Enums\StatutDemande::MIS_A_DISPOSITION->value]);
            $demandesAJustifier = $stmt->fetchAll();

            // Justifiées : mis_a_disposition et is_justified = 1
            $stmt = $db->prepare("
                SELECT d.*, u.nom, u.prenom 
                FROM demandes d 
                JOIN users u ON d.user_id = u.id 
                WHERE d.statut = ? AND d.is_justified = 1
                ORDER BY d.updated_at DESC
            ");
            $stmt->execute([\App\Enums\StatutDemande::MIS_A_DISPOSITION->value]);
            $demandesJustifiees = $stmt->fetchAll();
        }
        
        $this->render('user/validation/index', [
            'demandes'           => $demandes,
            'demandesPassees'    => $demandesPassees,
            'demandesRejetees'   => $demandesRejetees,
            'demandesAJustifier' => $demandesAJustifier,
            'demandesJustifiees' => $demandesJustifiees,
            'title' => 'Validations en attente',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Validations', 'url' => '/validations']
            ]
        ]);
    }

    public function approve(int $id): void
    {
        CsrfMiddleware::handle();
        $commentaire = $_POST['commentaire'] ?? '';
        
        if ($this->validationService->validate($id, \App\Core\AuthHelper::getUserId(), $commentaire)) {
            $_SESSION['flash_success'] = "Demande validée avec succès.";
        } else {
            $_SESSION['flash_error'] = "Action non autorisée.";
        }
        $this->redirect('/validations');
    }

    public function reject(int $id): void
    {
        CsrfMiddleware::handle();
        $commentaire = $_POST['commentaire'] ?? '';
        if ($this->validationService->reject($id, \App\Core\AuthHelper::getUserId(), $commentaire)) {
            $_SESSION['flash_success'] = "Demande rejetée.";
        } else {
            $_SESSION['flash_error'] = "Action non autorisée.";
        }
        $this->redirect('/validations');
    }

    /**
     * Marque une demande comme justifiée (is_justified = 1). Réservé RA.
     */
    public function justify(int $id): void
    {
        CsrfMiddleware::handle();

        if (!\App\Core\AuthHelper::isRA()) {
            $this->redirect('/validations');
            return;
        }

        $db = \App\Core\Database::getInstance();
        // Vérifie que la demande est bien mis_a_disposition et pas encore justifiée
        $stmt = $db->prepare("SELECT id FROM demandes WHERE id = ? AND statut = ? AND is_justified = 0");
        $stmt->execute([$id, \App\Enums\StatutDemande::MIS_A_DISPOSITION->value]);
        if (!$stmt->fetch()) {
            $_SESSION['flash_error'] = "Action non autorisée sur cette demande.";
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/validations');
            return;
        }

        $stmt = $db->prepare("UPDATE demandes SET is_justified = 1, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['flash_success'] = "Demande marquée comme justifiée.";
            try {
                \App\Services\NotificationService::notifyJustification($id, \App\Core\AuthHelper::getUserName());
            } catch (\Exception $ne) {
                error_log("Failed to send justification notification: " . $ne->getMessage());
            }
        } else {
            $_SESSION['flash_error'] = "Une erreur est survenue.";
        }
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/validations');
    }

    /**
     * Annule la justification d'une demande (is_justified = 0). Réservé RA.
     */
    public function rollback(int $id): void
    {
        CsrfMiddleware::handle();

        if (!\App\Core\AuthHelper::isRA()) {
            $this->redirect('/validations');
            return;
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM demandes WHERE id = ? AND statut = ? AND is_justified = 1");
        $stmt->execute([$id, \App\Enums\StatutDemande::MIS_A_DISPOSITION->value]);
        if (!$stmt->fetch()) {
            $_SESSION['flash_error'] = "Action non autorisée sur cette demande.";
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/validations');
            return;
        }

        $stmt = $db->prepare("UPDATE demandes SET is_justified = 0, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['flash_success'] = "Justification annulée avec succès.";
            try {
                \App\Services\NotificationService::notifyRollbackJustification($id, \App\Core\AuthHelper::getUserName());
            } catch (\Exception $ne) {
                error_log("Failed to send rollback justification notification: " . $ne->getMessage());
            }
        } else {
            $_SESSION['flash_error'] = "Une erreur est survenue.";
        }
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/validations');
    }

    /**
     * Liste toutes les demandes mises à disposition par le RA connecté.
     */
    public function etats(): void
    {
        if (!\App\Core\AuthHelper::isRA()) {
            $this->redirect('/dashboard');
            return;
        }

        $db = \App\Core\Database::getInstance();
        $userId = \App\Core\AuthHelper::getUserId();

        // Récupérer les demandes validées par cet RA
        $stmt = $db->prepare("
            SELECT d.*, u.nom, u.prenom, v.created_at AS date_mise_a_disposition
            FROM demandes d
            JOIN users u ON d.user_id = u.id
            JOIN validations v ON v.demande_id = d.id
            WHERE v.validateur_id = ?
              AND v.action = 'validation'
              AND v.etape = ?
            ORDER BY v.created_at DESC
        ");
        $stmt->execute([
            $userId,
            \App\Enums\EtapeValidation::RESPONSABLE_ADMINISTRATIF->value
        ]);
        $demandes = $stmt->fetchAll();

        $this->render('user/validation/etats', [
            'demandes' => $demandes,
            'title' => __('etats'),
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => __('etats'), 'url' => '/etats']
            ]
        ]);
    }
}
