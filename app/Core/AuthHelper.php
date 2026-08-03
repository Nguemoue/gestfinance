<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\CategorieUtilisateur;
use App\Enums\SpaceEnum;

/**
 * Helper pour gérer l'authentification et les droits d'accès sans manipuler $_SESSION directement.
 */
class AuthHelper
{
    /**
     * Vérifie si un utilisateur est connecté.
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Récupère l'ID de l'utilisateur connecté.
     */
    public static function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Récupère le nom complet de l'utilisateur.
     */
    public static function getUserName(): string
    {
        return $_SESSION['user_name'] ?? 'Invité';
    }

    /**
     * Récupère la catégorie (rôle métier) de l'utilisateur.
     */
    public static function getRoleCode(): ?string
    {
        return $_SESSION['role_code'] ?? null;
    }

    public static function getRoleLabel(): string
    {
        $role = CategorieUtilisateur::tryFrom((string) self::getRoleCode());
        return $role?->label() ?? 'Rôle inconnu';
    }

    /**
     * Récupère l'espace de connexion actuel (admin ou user).
     */
    public static function getSpace(): string
    {
        return $_SESSION['user_space'] ?? 'user';
    }

    /**
     * Récupère l'ID du service de l'utilisateur.
     */
    public static function getPrimaryServiceId(): ?int
    {
        return isset($_SESSION['primary_service_id']) ? (int) $_SESSION['primary_service_id'] : null;
    }

    public static function getServiceIds(): array
    {
        return array_map('intval', $_SESSION['service_ids'] ?? []);
    }

    /**
     * Vérifie si l'utilisateur est un Agent.
     */
    public static function isAgent(): bool
    {
        return self::getRoleCode() === CategorieUtilisateur::AGENT->value;
    }

    /**
     * Vérifie si l'utilisateur est un Responsable/Directeur.
     */
    public static function isDirector(): bool
    {
        return self::getRoleCode() === CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value;
    }

    /**
     * Vérifie si l'utilisateur est le DG.
     */
    public static function isDG(): bool
    {
        return self::getRoleCode() === CategorieUtilisateur::DG->value;
    }

    /**
     * Vérifie si l'utilisateur est le Responsable Administratif (RA).
     */
    public static function isRA(): bool
    {
        return in_array(self::getRoleCode(), [
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
        ], true);
    }

    public static function isRAChief(): bool
    {
        return self::getRoleCode() === CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value;
    }

    public static function isRADeputy(): bool
    {
        return self::getRoleCode() === CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value;
    }

    /**
     * Vérifie si l'utilisateur est dans l'espace Administration.
     */
    public static function isAdminSpace(): bool
    {
        return self::getSpace() === SpaceEnum::ADMIN->value || (self::getSpace() === SpaceEnum::SUPER_ADMIN->value);
    }

    public static function isUserSpace(): bool
    {
        return self::getSpace() === SpaceEnum::USER->value;
    }

    public static function isSuperAdminSpace(): bool
    {
        return self::getSpace() === SpaceEnum::SUPER_ADMIN->value;
    }
    
    
}
