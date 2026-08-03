<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enums\CategorieUtilisateur;

/**
 * Middleware pour vérifier les rôles des utilisateurs.
 */
class RoleMiddleware
{
    public static function handle(array $allowedCategories): void
    {
        AuthMiddleware::handle();

        $roleCode = \App\Core\AuthHelper::getRoleCode();

        if (!in_array($roleCode, $allowedCategories, true)) {
            http_response_code(403);
            die("Accès interdit : vous n'avez pas les droits nécessaires.");
        }
    }
}
