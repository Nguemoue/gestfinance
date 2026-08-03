<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum représentant les catégories d'utilisateurs.
 */
enum CategorieUtilisateur: string
{
    case AGENT = 'agent';
    case RESPONSABLE_DIRECTEUR = 'responsable_directeur';
    case DG = 'dg';
    case RESPONSABLE_ADMINISTRATIF = 'responsable_administratif';
    case RESPONSABLE_ADMINISTRATIF_ADJOINT = 'responsable_administratif_adjoint';
    case SUPER_ADMIN = 'super_admin';

    /**
     * Retourne le libellé lisible de la catégorie.
     */
    public function label(): string
    {
        return match ($this) {
            self::AGENT => 'Agent',
            self::RESPONSABLE_DIRECTEUR => 'Responsable / Directeur',
            self::DG => 'Directeur Général',
            self::SUPER_ADMIN=>'Super Administrateur',
            self::RESPONSABLE_ADMINISTRATIF => 'Responsable Administratif (Chef)',
            self::RESPONSABLE_ADMINISTRATIF_ADJOINT => 'Responsable Administratif Adjoint (Sous-chef)',
        };
    }

    /**
     * Indique si ce rôle peut être désigné responsable d'un service.
     */
    public function canManageService(): bool
    {
        return match ($this) {
            self::RESPONSABLE_DIRECTEUR,
            self::DG,
            self::RESPONSABLE_ADMINISTRATIF,
            self::RESPONSABLE_ADMINISTRATIF_ADJOINT => true,
            default => false,
        };
    }

    /**
     * Codes utilisés pour filtrer les responsables de service en base.
     *
     * @return list<string>
     */
    public static function serviceManagerCodes(): array
    {
        return array_values(array_map(
            static fn(self $role): string => $role->value,
            array_filter(self::cases(), static fn(self $role): bool => $role->canManageService())
        ));
    }
}
