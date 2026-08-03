<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Middleware\RoleMiddleware;
use App\Enums\CategorieUtilisateur;
use App\Middleware\CsrfMiddleware;

class ServiceController extends Controller
{
    private Service $serviceModel;

    public function __construct()
    {
        RoleMiddleware::handle([CategorieUtilisateur::DG->value, CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value, CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value, CategorieUtilisateur::SUPER_ADMIN->value]);
        $this->serviceModel = new Service();
    }

    public function index(): void
    {
        // user_services est la source de vérité des responsables.
        $db = \App\Core\Database::getInstance();
        $services = $db->query("
            SELECT s.*,
                   GROUP_CONCAT(
                       DISTINCT CONCAT(u.prenom, ' ', u.nom)
                       ORDER BY u.nom, u.prenom SEPARATOR ', '
                   ) AS responsables_noms
            FROM services s
            LEFT JOIN user_services us
                ON us.service_id = s.id AND us.is_responsable = 1
            LEFT JOIN users u
                ON u.id = us.user_id AND u.is_active = 1
            GROUP BY s.id
            ORDER BY s.libelle ASC
        ")->fetchAll();

        $this->render('admin/services/index', [
            'services' => $services,
            'title' => 'Gestion des Services',
            'breadcrumbs' => [['label' => 'Accueil', 'url' => '/'], ['label' => 'Services', 'url' => '/admin/services']]
        ]);
    }

    public function create(): void
    {
        $this->render('admin/services/create', [
            'users' => $this->responsibleCandidates(),
            'title' => 'Nouveau Service',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Services', 'url' => '/admin/services'],
                ['label' => 'Nouveau', 'url' => '/admin/services/create']
            ]
        ]);
    }

    public function store(): void
    {
        CsrfMiddleware::handle();
        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        try {
            $data = $this->getFormData();
            $this->serviceModel->create($data);
            $this->syncResponsibles(
                (int) $db->lastInsertId(),
                $this->getResponsibleIds()
            );
            $db->commit();
            $_SESSION['flash_success'] = "Service créé.";
            $this->redirect('/admin/services');
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Erreur lors de la création.";
            $this->redirect('/admin/services/create');
        }
    }

    public function edit(int $id): void
    {
        $service = $this->serviceModel->find($id);
        if (!$service) $this->redirect('/admin/services');

        $this->render('admin/services/edit', [
            'service' => $service,
            'users' => $this->responsibleCandidates(),
            'selectedResponsibleIds' => $this->responsibleIdsForService($id),
            'title' => 'Modifier le Service',
            'breadcrumbs' => [
                ['label' => 'Accueil', 'url' => '/'],
                ['label' => 'Services', 'url' => '/admin/services'],
                ['label' => 'Modifier', 'url' => '#']
            ]
        ]);
    }

    public function update(int $id): void
    {
        CsrfMiddleware::handle();
        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        try {
            if (!$this->serviceModel->find($id)) {
                throw new \RuntimeException('Service introuvable.');
            }
            $data = $this->getFormData();
            $this->serviceModel->update($id, $data);
            $this->syncResponsibles($id, $this->getResponsibleIds());
            $db->commit();
            $_SESSION['flash_success'] = "Service mis à jour.";
            $this->redirect('/admin/services');
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = "Erreur lors de la mise à jour du service.";
            $this->redirect("/admin/services/edit/$id");
        }
    }

    public function delete(int $id): void
    {
        CsrfMiddleware::handle();
        $this->serviceModel->delete($id);
        $_SESSION['flash_success'] = "Service supprimé.";
        $this->redirect('/admin/services');
    }

    private function getFormData(): array
    {
        return [
            'libelle' => trim((string) ($_POST['libelle'] ?? '')),
            'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
    }

    private function responsibleCandidates(): array
    {
        $db = \App\Core\Database::getInstance();
        return $db->query(
            "SELECT u.id, u.nom, u.prenom, r.code AS role_code
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1
               AND r.is_active = 1
               AND r.code IN (
                   'responsable_directeur',
                   'responsable_administratif',
                   'responsable_administratif_adjoint',
                   'dg'
               )
             ORDER BY FIELD(
                 r.code,
                 'responsable_directeur',
                 'responsable_administratif',
                 'responsable_administratif_adjoint',
                 'dg'
             ), u.nom, u.prenom"
        )->fetchAll();
    }

    private function getResponsibleIds(): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            $_POST['responsable_ids'] ?? []
        ))));
        $eligibleIds = array_map(
            static fn(array $user): int => (int) $user['id'],
            $this->responsibleCandidates()
        );
        if (array_diff($ids, $eligibleIds) !== []) {
            throw new \InvalidArgumentException('Un responsable sélectionné n’est pas éligible.');
        }
        return $ids;
    }

    private function responsibleIdsForService(int $serviceId): array
    {
        $stmt = \App\Core\Database::getInstance()->prepare(
            'SELECT user_id FROM user_services WHERE service_id = ? AND is_responsable = 1 ORDER BY user_id'
        );
        $stmt->execute([$serviceId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    private function syncResponsibles(int $serviceId, array $userIds): void
    {
        $db = \App\Core\Database::getInstance();
        $db->prepare('UPDATE user_services SET is_responsable = 0 WHERE service_id = ?')->execute([$serviceId]);
        $insert = $db->prepare(
            'INSERT INTO user_services (user_id, service_id, is_primary, is_responsable)
             VALUES (?, ?, 0, 1)
             ON DUPLICATE KEY UPDATE is_responsable = 1'
        );
        foreach ($userIds as $userId) {
            $insert->execute([$userId, $serviceId]);
        }
    }
}
