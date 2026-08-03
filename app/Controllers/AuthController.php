<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Enums\CategorieUtilisateur;
use App\Enums\SpaceEnum;
use App\Middleware\AuthMiddleware;
use App\Models\User;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;

/**
 * Contrôleur pour l'authentification.
 */
class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Affiche la landing page.
     */
    public function landing(): void
    {
        //AuthMiddleware::handle();
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->render('landing');
    }

    /**
     * Affiche la page de connexion.
     */
    public function showLogin(): void
    {
        $this->render('auth/login', ['title' => 'Connexion']);
    }

    /**
     * Gère la soumission du formulaire de connexion.
     */
    public function login(): void
    {
        try {
            CsrfMiddleware::handle();
            RateLimitMiddleware::handle();

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {

                $roleCode = $user['role_code'];
                $space = match ($roleCode) {
                    CategorieUtilisateur::DG->value => SpaceEnum::ADMIN->value,
                    CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value => SpaceEnum::ADMIN->value,
                    CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value => SpaceEnum::ADMIN->value,
                    CategorieUtilisateur::SUPER_ADMIN->value => SpaceEnum::SUPER_ADMIN->value,
                    CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value => SpaceEnum::ADMIN->value,
                    default => SpaceEnum::USER->value,
                };

                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['role_code'] = $roleCode;
                $_SESSION['service_ids'] = \App\Models\UserService::serviceIdsForUser((int) $user['id']);
                $_SESSION['primary_service_id'] = \App\Models\UserService::primaryServiceIdForUser((int) $user['id']);
                $_SESSION['user_space'] = $space;

                $_SESSION['flash_success'] = "Bienvenue, {$_SESSION['user_name']} !";
                $this->redirect('/dashboard');
            } else {
                $_SESSION['flash_error'] = "Identifiants invalides.";
                $this->redirect('/login');
            }
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = "Erreur : " . $e->getMessage();
            $this->redirect('/');
        }
    }

    /**
     * Déconnecte l'utilisateur.
     */
    public function logout(): void
    {
        CsrfMiddleware::handle();
        session_destroy();
        session_start();
        $_SESSION['flash_success'] = "Vous avez été déconnecté.";
        $this->redirect('/');
    }
}
