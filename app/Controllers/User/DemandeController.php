<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\Controller;
use App\Models\Demande;
use App\Models\Service;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Enums\StatutDemande;

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
            LEFT JOIN services s ON u.service_id = s.id 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([\App\Core\AuthHelper::getUserId()]);
        $currentUserDetails = $stmt->fetch() ?: [];

        $this->render('user/demandes/create', [
            'services' => (new Service())->all(),
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
            $cat = \App\Core\AuthHelper::getCategory();
            if ($cat === \App\Enums\CategorieUtilisateur::DG->value) {
                $statut = StatutDemande::VALIDE_DG->value;
            } elseif ($cat === \App\Enums\CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value) {
                $statut = StatutDemande::VALIDE_DIRECTEUR->value;
            } elseif ($cat === \App\Enums\CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value) {
                $statut = StatutDemande::VALIDE_DIRECTEUR->value;
            } else {
                $statut = StatutDemande::SOUMIS->value;
            }
        }

        $data = [
            'user_id' => \App\Core\AuthHelper::getUserId(),
            'service_id' => $_POST['service_id'],
            'fonction' => $_POST['fonction'],
            'objet' => $_POST['objet'],
            'montant' => $_POST['montant'],
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
        if ($demande['user_id'] != \App\Core\AuthHelper::getUserId() && \App\Core\AuthHelper::isAgent()) {
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
}
