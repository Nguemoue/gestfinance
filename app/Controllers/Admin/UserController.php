<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\User;
use App\Models\Service;
use App\Models\Role;
use App\Middleware\RoleMiddleware;
use App\Enums\CategorieUtilisateur;
use App\Core\Database;
use App\Middleware\CsrfMiddleware;
use App\Models\UserService;

/**
 * Contrôleur pour la gestion des utilisateurs par l'administrateur.
 */
class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        RoleMiddleware::handle([
            CategorieUtilisateur::DG->value,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
            CategorieUtilisateur::SUPER_ADMIN->value,
        ]);
        $this->userModel = new User();
    }

    public function index(): void
    {
        $users = $this->userModel->allWithRoles();
        $this->render('admin/users/index', [
            'users' => $users,
            'title' => 'Gestion des Utilisateurs',
            'breadcrumbs' => [['label' => 'Accueil', 'url' => '/'], ['label' => 'Utilisateurs', 'url' => '/admin/users']]
        ]);
    }

    public function create(): void
    {
        $this->render('admin/users/create', [
            'services' => (new Service())->all(),
            'roles' => (new Role())->allCanonical(),
            'title' => 'Créer un utilisateur',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Utilisateurs', 'url' => '/admin/users'],
                ['label' => 'Nouveau', 'url' => '/admin/users/create']
            ]
        ]);
    }

    public function store(): void
    {
        CsrfMiddleware::handle();
        $data = $this->getFormData();
        $serviceIds = $this->getServiceIdsFromForm();
        $roleCode = $this->roleCodeFromId((int) $data['role_id']);
        if (!$this->canAssignRole($roleCode, (bool) $data['is_active'])) {
            $_SESSION['flash_error'] = $this->roleLimitMessage($roleCode);
            $this->redirect('/admin/users/create');
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $this->userModel->create($data);
            $userId = (int) $db->lastInsertId();
            UserService::sync($userId, $serviceIds, $serviceIds[0] ?? null);
            $db->commit();
            $_SESSION['flash_success'] = "Utilisateur créé avec succès.";
            $this->redirect('/admin/users');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Erreur lors de la création de l'utilisateur.";
            $this->redirect('/admin/users/create');
        }
    }

    public function edit(int $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            $_SESSION['flash_error'] = "Utilisateur introuvable.";
            $this->redirect('/admin/users');
        }

        $this->render('admin/users/edit', [
            'user' => $user,
            'services' => (new Service())->all(),
            'roles' => (new Role())->allCanonical(),
            'selectedServiceIds' => UserService::serviceIdsForUser($id),
            'title' => 'Modifier l\'utilisateur',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Utilisateurs', 'url' => '/admin/users'],
                ['label' => 'Modifier', 'url' => "#"]
            ]
        ]);
    }

    public function update(int $id): void
    {
        CsrfMiddleware::handle();
        $data = $this->getFormData();
        $serviceIds = $this->getServiceIdsFromForm();
        $roleCode = $this->roleCodeFromId((int) $data['role_id']);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (!$this->canAssignRole($roleCode, (bool) $data['is_active'], $id)) {
            $_SESSION['flash_error'] = $this->roleLimitMessage($roleCode);
            $this->redirect("/admin/users/edit/$id");
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $this->userModel->update($id, $data);
            UserService::sync($id, $serviceIds, $serviceIds[0] ?? null);
            $db->commit();
            $_SESSION['flash_success'] = "Utilisateur mis à jour.";
            $this->redirect('/admin/users');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Erreur lors de la mise à jour.";
            $this->redirect("/admin/users/edit/$id");
        }
    }

    public function delete(int $id): void
    {
        CsrfMiddleware::handle();
        if ($this->userModel->delete($id)) {
            $_SESSION['flash_success'] = "Utilisateur supprimé.";
        } else {
            $_SESSION['flash_error'] = "Erreur lors de la suppression.";
        }
        $this->redirect('/admin/users');
    }

    private function getFormData(): array
    {
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $this->roleCodeFromId((int) $roleId);

        return [
            'nom' => trim((string) ($_POST['nom'] ?? '')),
            'prenom' => trim((string) ($_POST['prenom'] ?? '')),
            'email' => filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)
                ?: throw new \InvalidArgumentException('Adresse e-mail invalide.'),
            'password' => $_POST['password'] ?? null,
            'role_id' => $roleId,
            'niveau_validation' => (int) ($_POST['niveau_validation'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function roleCodeFromId(int $roleId): string
    {
        $role = $roleId > 0 ? (new Role())->find($roleId) : null;
        $roleCode = $role ? strtolower((string) $role['code']) : '';
        if (!CategorieUtilisateur::tryFrom($roleCode)) {
            throw new \InvalidArgumentException('Rôle invalide.');
        }
        return $roleCode;
    }

    private function getServiceIdsFromForm(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'intval',
            $_POST['service_ids'] ?? []
        ))));
    }

    private function canAssignRole(string $roleCode, bool $isActive, ?int $exceptUserId = null): bool
    {
        if (!$isActive) {
            return true;
        }
        if (!in_array($roleCode, [
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
        ], true)) {
            return true;
        }
        return $this->userModel->countActiveByRoleCode($roleCode, $exceptUserId) < 1;
    }

    private function roleLimitMessage(string $roleCode): string
    {
        return $roleCode === CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value
            ? 'Un responsable administratif chef actif existe déjà.'
            : 'Un responsable administratif adjoint actif existe déjà.';
    }
}
