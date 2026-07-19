<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Models\User;
use App\Models\Service;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

class ProfileController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        AuthMiddleware::handle();
        $this->userModel = new User();
    }

    public function index(): void
    {
        $user = $this->userModel->find(AuthHelper::getUserId());
        $service = (new Service())->find($user['service_id'] ?? 0);

        $this->render('user/profile', [
            'user' => $user,
            'service' => $service,
            'title' => 'Mon Profil',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Mon Profil', 'url' => '/profile']
            ]
        ]);
    }

    public function update(): void
    {
        CsrfMiddleware::handle();

        $userId = AuthHelper::getUserId();
        $data = [
            //'nom' => $_POST['nom'],
            //'prenom' => $_POST['prenom'],
            //'email' => $_POST['email'],
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        if (empty($data)) {
            $_SESSION['flash_success'] = "Aucune modification.";
            $this->redirect('/profile');
        }
        if ($this->userModel->update($userId, $data)) {
            $_SESSION['user_name'] = $data['prenom'] . ' ' . $data['nom'];
            $_SESSION['flash_success'] = "Profil mis à jour avec succès.";
        } else {
            $_SESSION['flash_error'] = "Erreur lors de la mise à jour.";
        }

        $this->redirect('/profile');
    }
}
