<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Enums\StatutDemande;
use App\Enums\CategorieUtilisateur;
use App\Models\UserService;

class ValidationService
{
    /**
     * Valide une demande par un utilisateur.
     */
    public function validate(int $demandeId, int $userId, string $commentaire): bool
    {
        
        $db = Database::getInstance();
        
        // 1. Récupérer la demande
        $stmt = $db->prepare("SELECT * FROM demandes WHERE id = ?");
        $stmt->execute([$demandeId]);
        $demande = $stmt->fetch();
        if (!$demande) {
            return false;
        }

        // 2. Récupérer le valideur
        $stmt = $db->prepare("SELECT u.*, r.code AS role_code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }

        $newStatus = $demande['statut'];
        $etape = '';

        // La responsabilité du service est contextuelle et prioritaire sur le rôle global.
        if (
            $demande['statut'] === StatutDemande::SOUMIS->value
            && UserService::isResponsibleFor($userId, (int) $demande['service_id'])
        ) {
            $newStatus = StatutDemande::VALIDE_DIRECTEUR->value;
            $etape = \App\Enums\EtapeValidation::DIRECTEUR->value;
        } elseif (
            $user['role_code'] === CategorieUtilisateur::DG->value
            && $demande['statut'] === StatutDemande::VALIDE_DIRECTEUR->value
        ) {
            $newStatus = StatutDemande::VALIDE_DG->value;
            $etape = \App\Enums\EtapeValidation::DG->value;
        } elseif (
            in_array($user['role_code'], [
                CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
                CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
            ], true)
            && $demande['statut'] === StatutDemande::VALIDE_DG->value
        ) {
            $newStatus = StatutDemande::MIS_A_DISPOSITION->value;
            $etape = \App\Enums\EtapeValidation::RESPONSABLE_ADMINISTRATIF->value;
        } else {
            return false;
        }

        $db->beginTransaction();
        try {
            // Mise à jour du statut
            $stmt = $db->prepare("UPDATE demandes SET statut = ?, updated_at = NOW() WHERE id = ? AND statut = ?");
            $stmt->execute([$newStatus, $demandeId, $demande['statut']]);
            if ($stmt->rowCount() !== 1) {
                throw new \RuntimeException('La demande a déjà été traitée.');
            }

            if ($newStatus === StatutDemande::MIS_A_DISPOSITION->value) {
                $stmt = $db->prepare(
                    "UPDATE demandes d
                     JOIN users u ON u.id = d.user_id
                     SET d.reference_etat = COALESCE(
                            d.reference_etat,
                            CONCAT('BF-', YEAR(d.created_at), '-', LPAD(d.id, 4, '0'))
                         ),
                         d.beneficiaire_etat = COALESCE(
                            NULLIF(d.beneficiaire_etat, ''),
                            TRIM(CONCAT(u.prenom, ' ', u.nom))
                         ),
                         d.date_mise_a_disposition = COALESCE(d.date_mise_a_disposition, NOW())
                     WHERE d.id = ?"
                );
                $stmt->execute([$demandeId]);
            }

            // Enregistrement de la validation
            $stmt = $db->prepare("INSERT INTO validations (demande_id, validateur_id, action, commentaire, etape) VALUES (?, ?, 'validation', ?, ?)");
            $stmt->execute([$demandeId, $userId, $commentaire, $etape]);

            $db->commit();

            // Envoi de la notification après commit
            try {
                $validatorName = $user['prenom'] . ' ' . $user['nom'];
                $roleEnum = \App\Enums\CategorieUtilisateur::tryFrom($user['role_code']);
                $validatorRole = $roleEnum ? $roleEnum->label() : $user['role_code'];
                \App\Services\NotificationService::notifyValidation($demandeId, $validatorName, $validatorRole, $newStatus, $commentaire);
            } catch (\Exception $ne) {
                error_log("Failed to send validation notification: " . $ne->getMessage());
            }

            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Rejette une demande.
     */
    public function reject(int $demandeId, int $userId, string $commentaire): bool
    {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM demandes WHERE id = ?");
        $stmt->execute([$demandeId]);
        $demande = $stmt->fetch();
        if (!$demande) {
            return false;
        }

        $stmt = $db->prepare("SELECT u.*, r.code AS role_code FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }

        if (
            $demande['statut'] === StatutDemande::SOUMIS->value
            && UserService::isResponsibleFor($userId, (int) $demande['service_id'])
        ) {
            $etape = \App\Enums\EtapeValidation::DIRECTEUR->value;
        } elseif (
            $user['role_code'] === CategorieUtilisateur::DG->value
            && $demande['statut'] === StatutDemande::VALIDE_DIRECTEUR->value
        ) {
            $etape = \App\Enums\EtapeValidation::DG->value;
        } elseif (
            in_array($user['role_code'], [
                CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
                CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
            ], true)
            && $demande['statut'] === StatutDemande::VALIDE_DG->value
        ) {
            $etape = \App\Enums\EtapeValidation::RESPONSABLE_ADMINISTRATIF->value;
        } else {
            return false;
        }

        $db->beginTransaction();
        try {
            // Mise à jour du statut
            $stmt = $db->prepare("UPDATE demandes SET statut = ?, updated_at = NOW() WHERE id = ? AND statut = ?");
            $stmt->execute([StatutDemande::REJETE->value, $demandeId, $demande['statut']]);
            if ($stmt->rowCount() !== 1) {
                throw new \RuntimeException('La demande a déjà été traitée.');
            }

            // Enregistrement du rejet
            $stmt = $db->prepare("INSERT INTO validations (demande_id, validateur_id, action, commentaire, etape) VALUES (?, ?, 'rejet', ?, ?)");
            $stmt->execute([$demandeId, $userId, $commentaire, $etape]);

            $db->commit();

            // Envoi de la notification après commit
            try {
                $validatorName = $user['prenom'] . ' ' . $user['nom'];
                $roleEnum = \App\Enums\CategorieUtilisateur::tryFrom($user['role_code']);
                $validatorRole = $roleEnum ? $roleEnum->label() : $user['role_code'];
                \App\Services\NotificationService::notifyRejection($demandeId, $validatorName, $validatorRole, $commentaire);
            } catch (\Exception $ne) {
                error_log("Failed to send rejection notification: " . $ne->getMessage());
            }

            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}
