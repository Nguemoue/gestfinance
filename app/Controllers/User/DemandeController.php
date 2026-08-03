<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Models\Demande;
use App\Models\Service;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Enums\StatutDemande;
use App\Services\AccessService;

/**
 * Contrôleur pour la gestion des demandes par les utilisateurs.
 */
class DemandeController extends Controller
{
    private Demande $demandeModel;

    public function __construct()
    {
        AuthMiddleware::handle();
        $this->demandeModel = new Demande();
    }

    /**
     * Liste les demandes de l'utilisateur connecté.
     */
    public function index(): void
    {
        $demandes = $this->demandeModel->findByUser(\App\Core\AuthHelper::getUserId());
        
        $this->render('user/demandes/index', [
            'demandes' => $demandes,
            'title' => 'Mes Demandes',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Mes Demandes', 'url' => '/demandes']
            ]
        ]);
    }

    /**
     * Formulaire de création d'une demande.
     */
    public function create(): void
    {
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.*, s.libelle as service_nom, r.libelle as role_nom
            FROM users u
            LEFT JOIN user_services us ON us.user_id = u.id AND us.is_primary = 1
            LEFT JOIN services s ON s.id = us.service_id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([\App\Core\AuthHelper::getUserId()]);
        $currentUserDetails = $stmt->fetch() ?: [];

        $this->render('user/demandes/create', [
            'services' => $db->query(
                "SELECT s.* FROM services s
                 JOIN user_services us ON us.service_id = s.id
                 WHERE us.user_id = " . (int) \App\Core\AuthHelper::getUserId() . " AND s.is_active = 1
                 ORDER BY us.is_primary DESC, s.libelle"
            )->fetchAll(),
            'currentUserDetails' => $currentUserDetails,
            'title' => 'Nouvelle Demande',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Mes Demandes', 'url' => '/demandes'],
                ['label' => 'Nouvelle', 'url' => '/demandes/create']
            ]
        ]);
    }

    /**
     * Enregistre une nouvelle demande.
     */
    public function store(): void
    {
        CsrfMiddleware::handle();

        $statut = StatutDemande::BROUILLON->value;
        if (isset($_POST['submit_action']) && $_POST['submit_action'] === 'soumettre') {
            $roleCode = \App\Core\AuthHelper::getRoleCode();
            if ($roleCode === \App\Enums\CategorieUtilisateur::DG->value) {
                $statut = StatutDemande::VALIDE_DG->value;
            } elseif (in_array($roleCode, [
                \App\Enums\CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
                \App\Enums\CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
            ], true)) {
                $statut = StatutDemande::VALIDE_DIRECTEUR->value;
            } elseif ($roleCode === \App\Enums\CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value) {
                $statut = StatutDemande::VALIDE_DIRECTEUR->value;
            } else {
                $statut = StatutDemande::SOUMIS->value;
            }
        }

        $data = [
            'user_id' => \App\Core\AuthHelper::getUserId(),
            'service_id' => $this->validatedServiceId(),
            'fonction' => trim((string) ($_POST['fonction'] ?? '')),
            'objet' => trim((string) ($_POST['objet'] ?? '')),
            'montant' => $this->validatedAmount(),
            'statut' => $statut,
        ];

        if ($this->demandeModel->create($data)) {
            $demandeId = (int)\App\Core\Database::getInstance()->lastInsertId();
            if ($statut !== StatutDemande::BROUILLON->value) {
                \App\Services\NotificationService::notifyCreation($demandeId);
            }
            $_SESSION['flash_success'] = "La demande a été enregistrée avec succès.";
            $this->redirect('/demandes');
        } else {
            $_SESSION['flash_error'] = "Une erreur est survenue lors de l'enregistrement.";
            $this->redirect('/demandes/create');
        }
    }

    /**
     * Affiche les détails d'une demande.
     */
    public function show(int $id): void
    {
        $demande = $this->demandeModel->findWithDetails($id);

        if (!$demande) {
            $_SESSION['flash_error'] = "Demande introuvable.";
            $this->redirect('/demandes');
        }

        // Vérifier l'accès (le demandeur ou un valideur concerné)
        // Pour simplifier : le demandeur
        if (!AccessService::canViewDemande($demande)) {
            http_response_code(403);
            die("Accès non autorisé.");
        }

        // Récupérer l'historique des validations
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT v.*, u.nom, u.prenom 
                             FROM validations v 
                             JOIN users u ON v.validateur_id = u.id 
                             WHERE v.demande_id = ? 
                             ORDER BY v.created_at ASC");
        $stmt->execute([$id]);
        $validations = $stmt->fetchAll();

        $this->render('user/demandes/show', [
            'demande' => $demande,
            'validations' => $validations,
            'title' => 'Détails de la demande',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Mes Demandes', 'url' => '/demandes'],
                ['label' => "Demande #" . $id, 'url' => '#']
            ]
        ]);
    }

    private function validatedServiceId(): int
    {
        $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
        if (!$serviceId || !in_array($serviceId, \App\Core\AuthHelper::getServiceIds(), true)) {
            throw new \InvalidArgumentException('Service invalide ou non rattaché à votre compte.');
        }
        return $serviceId;
    }

    private function validatedAmount(): string
    {
        $amount = filter_var($_POST['montant'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($amount === false || $amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être strictement positif.');
        }
        return number_format($amount, 2, '.', '');
    }
}
