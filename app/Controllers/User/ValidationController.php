<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Services\ValidationService;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Enums\CategorieUtilisateur;
use App\Services\PdfService;

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
        $roleCode = \App\Core\AuthHelper::getRoleCode();
        $role = CategorieUtilisateur::tryFrom($roleCode);
        if (!$role || (!$role->canManageService() && $role !== CategorieUtilisateur::SUPER_ADMIN)) {
            $this->redirect('/');
            return;
        }

        $db = \App\Core\Database::getInstance();
        $userId = \App\Core\AuthHelper::getUserId();

        $isDg = $roleCode === CategorieUtilisateur::DG->value;
        $isAdministrativeManager = in_array($roleCode, [
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
        ], true);

        // Une même personne peut cumuler les demandes de tous les services dont elle
        // est responsable et celles liées à son rôle global (DG ou RA).
        $query = "SELECT DISTINCT d.*, u.nom, u.prenom
                  FROM demandes d
                  JOIN users u ON d.user_id = u.id
                  WHERE (
                      d.statut = :soumis
                      AND EXISTS (
                          SELECT 1
                          FROM user_services us
                          WHERE us.service_id = d.service_id
                            AND us.user_id = :userId
                            AND us.is_responsable = 1
                      )
                  )
                  OR (:isDg = 1 AND d.statut = :valideDirecteur)
                  OR (:isRa = 1 AND d.statut = :valideDg)
                  ORDER BY d.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([
            'soumis' => \App\Enums\StatutDemande::SOUMIS->value,
            'userId' => $userId,
            'isDg' => $isDg ? 1 : 0,
            'valideDirecteur' => \App\Enums\StatutDemande::VALIDE_DIRECTEUR->value,
            'isRa' => $isAdministrativeManager ? 1 : 0,
            'valideDg' => \App\Enums\StatutDemande::VALIDE_DG->value,
        ]);
        $demandes = $stmt->fetchAll();

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
        if (\App\Core\AuthHelper::isRA()) {
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
        // Registre partagé entre les deux responsables administratifs.
        $stmt = $db->prepare($this->etatsQuery() . "
            ORDER BY COALESCE(d.date_mise_a_disposition, v.created_at) DESC
        ");
        $stmt->execute([
            \App\Enums\EtapeValidation::RESPONSABLE_ADMINISTRATIF->value,
            \App\Enums\StatutDemande::MIS_A_DISPOSITION->value,
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

    public function exportEtatsPdf(): void
    {
        if (!\App\Core\AuthHelper::isRA()) {
            http_response_code(403);
            echo 'Accès non autorisé.';
            return;
        }

        [$filtersSql, $filtersParams, $filters] = $this->etatsFilters($_GET);
        $stmt = \App\Core\Database::getInstance()->prepare(
            $this->etatsQuery() . $filtersSql . "
            ORDER BY COALESCE(d.date_mise_a_disposition, v.created_at) DESC"
        );
        $stmt->execute([
            \App\Enums\EtapeValidation::RESPONSABLE_ADMINISTRATIF->value,
            \App\Enums\StatutDemande::MIS_A_DISPOSITION->value,
            ...$filtersParams,
        ]);
        $demandes = $stmt->fetchAll();
        $generatedAt = new \DateTimeImmutable();

        ob_start();
        include __DIR__ . '/../../../views/fiche/etats.php';
        $html = (string) ob_get_clean();

        (new PdfService())->generate(
            $html,
            'Etats_filtres_' . $generatedAt->format('Ymd_His') . '.pdf',
            true,
            'landscape'
        );
    }

    private function etatsQuery(): string
    {
        return "
            SELECT d.*, u.nom, u.prenom, s.libelle AS service_nom,
                   COALESCE(d.date_mise_a_disposition, v.created_at) AS date_mise_a_disposition
            FROM demandes d
            JOIN users u ON d.user_id = u.id
            JOIN services s ON s.id = d.service_id
            LEFT JOIN (
                SELECT demande_id, MAX(created_at) AS created_at
                FROM validations
                WHERE action = 'validation' AND etape = ?
                GROUP BY demande_id
            ) v ON v.demande_id = d.id
            WHERE d.statut = ?
        ";
    }

    private function etatsFilters(array $input): array
    {
        $definitions = [
            'search' => ["CONCAT_WS(' ', d.reference_etat, u.prenom, u.nom, s.libelle, d.objet, d.montant) LIKE ?", true],
            'reference' => ['d.reference_etat LIKE ?', true],
            'beneficiaire' => ["CONCAT_WS(' ', d.beneficiaire_etat, u.prenom, u.nom) LIKE ?", true],
            'service' => ['s.libelle LIKE ?', true],
            'objet' => ['d.objet LIKE ?', true],
            'montant_min' => ['d.montant >= ?', false],
            'montant_max' => ['d.montant <= ?', false],
            'date_from' => ['DATE(COALESCE(d.date_mise_a_disposition, v.created_at)) >= ?', false],
            'date_to' => ['DATE(COALESCE(d.date_mise_a_disposition, v.created_at)) <= ?', false],
            'is_justified' => ['d.is_justified = ?', false],
        ];
        $sql = '';
        $params = [];
        $filters = [];

        foreach ($definitions as $key => [$condition, $contains]) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value === '' || ($key === 'is_justified' && !in_array($value, ['0', '1'], true))) {
                continue;
            }
            if (in_array($key, ['montant_min', 'montant_max'], true) && !is_numeric($value)) {
                continue;
            }
            if (in_array($key, ['date_from', 'date_to'], true)) {
                $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
                if (!$date || $date->format('Y-m-d') !== $value) {
                    continue;
                }
            }
            $sql .= ' AND ' . $condition;
            $params[] = $contains ? '%' . $value . '%' : $value;
            $filters[$key] = $value;
        }

        return [$sql, $params, $filters];
    }
}
