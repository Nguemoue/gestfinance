<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AuthHelper;
use App\Enums\CategorieUtilisateur;
use App\Models\UserService;

final class AccessService
{
    public static function canViewDemande(array $demande): bool
    {
        $userId = AuthHelper::getUserId();
        if ($userId === null) {
            return false;
        }

        if ((int) $demande['user_id'] === $userId) {
            return true;
        }

        return match (AuthHelper::getRoleCode()) {
            CategorieUtilisateur::SUPER_ADMIN->value,
            CategorieUtilisateur::DG->value,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value => true,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value => true,
            CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value =>
                UserService::isResponsibleFor($userId, (int) $demande['service_id']),
            default => false,
        };
    }
}
