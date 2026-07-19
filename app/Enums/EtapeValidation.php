<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum représentant les différentes étapes de validation d'une demande.
 */
enum EtapeValidation: string
{
    case DIRECTEUR = 'directeur';
    case RESPONSABLE_ADMINISTRATIF = 'responsable_administratif';
    case DG = 'dg';

    /**
     * Retourne le libellé lisible de l'étape.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIRECTEUR => 'Validation Chef de Service',
            self::DG => 'Approbation DG',
            self::RESPONSABLE_ADMINISTRATIF => 'Mise à disposition',
        };
    }
}
