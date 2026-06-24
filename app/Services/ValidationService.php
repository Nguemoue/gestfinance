<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Enums\StatutDemande;
use App\Enums\CategorieUtilisateur;

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

        // 2. Récupérer le valideur
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        $newStatus = $demande['statut'];
        $etape = '';

        // Logique de transition
        switch ($user['categorie']) {
            case CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value:
                if ($demande['statut'] === StatutDemande::SOUMIS->value) {
                    // Restriction par service : le valideur doit être le responsable du service de la demande
                    $stmtService = $db->prepare("SELECT responsable_id FROM services WHERE id = ?");
                    $stmtService->execute([$demande['service_id']]);
                    $respId = $stmtService->fetchColumn();
                    if ($respId && (int)$respId !== $userId) {
                        return false;
                    }
                    $newStatus = StatutDemande::VALIDE_DIRECTEUR->value;
                    $etape = \App\Enums\EtapeValidation::DIRECTEUR->value;
                } else {
                    return false;
                }
                break;

            case CategorieUtilisateur::DG->value:
                if ($demande['statut'] === StatutDemande::SOUMIS->value || $demande['statut'] === StatutDemande::VALIDE_DIRECTEUR->value) {
                    $newStatus = StatutDemande::VALIDE_DG->value;
                    $etape = \App\Enums\EtapeValidation::DG->value;
                } else {
                    return false;
                }
                break;

            case CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value:
                if ($demande['statut'] === StatutDemande::VALIDE_DG->value) {
                    $newStatus = StatutDemande::MIS_A_DISPOSITION->value;
                    $etape = \App\Enums\EtapeValidation::RESPONSABLE_ADMINISTRATIF->value;
                } else {
                    return false;
                }
                break;
            
            default:
                return false;
        }

        $db->beginTransaction();
        try {
            // Mise à jour du statut
            $stmt = $db->prepare("UPDATE demandes SET statut = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $demandeId]);

            // Enregistrement de la validation
            $stmt = $db->prepare("INSERT INTO validations (demande_id, validateur_id, action, commentaire, etape) VALUES (?, ?, 'validation', ?, ?)");
            $stmt->execute([$demandeId, $userId, $commentaire, $etape]);

            $db->commit();

            // Envoi de la notification après commit
            try {
                $validatorName = $user['prenom'] . ' ' . $user['nom'];
                $catEnum = \App\Enums\CategorieUtilisateur::tryFrom($user['categorie']);
                $validatorRole = $catEnum ? $catEnum->label() : $user['categorie'];
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

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }

        $etape = '';
        switch ($user['categorie']) {
            case CategorieUtilisateur::RESPONSABLE_DIRECTEUR->value:
                if ($demande['statut'] !== StatutDemande::SOUMIS->value) {
                    return false;
                }
                $stmtService = $db->prepare("SELECT responsable_id FROM services WHERE id = ?");
                $stmtService->execute([$demande['service_id']]);
                $respId = $stmtService->fetchColumn();
                if ($respId && (int)$respId !== $userId) {
                    return false;
                }
                $etape = \App\Enums\EtapeValidation::DIRECTEUR->value;
                break;

            case CategorieUtilisateur::DG->value:
                if ($demande['statut'] !== StatutDemande::SOUMIS->value && $demande['statut'] !== StatutDemande::VALIDE_DIRECTEUR->value) {
                    return false;
                }
                $etape = \App\Enums\EtapeValidation::DG->value;
                break;

            case CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value:
                if ($demande['statut'] !== StatutDemande::VALIDE_DG->value) {
                    return false;
                }
                $etape = \App\Enums\EtapeValidation::RESPONSABLE_ADMINISTRATIF->value;
                break;

            default:
                return false;
        }

        $db->beginTransaction();
        try {
            // Mise à jour du statut
            $stmt = $db->prepare("UPDATE demandes SET statut = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([StatutDemande::REJETE->value, $demandeId]);

            // Enregistrement du rejet
            $stmt = $db->prepare("INSERT INTO validations (demande_id, validateur_id, action, commentaire, etape) VALUES (?, ?, 'rejet', ?, ?)");
            $stmt->execute([$demandeId, $userId, $commentaire, $etape]);

            $db->commit();

            // Envoi de la notification après commit
            try {
                $validatorName = $user['prenom'] . ' ' . $user['nom'];
                $catEnum = \App\Enums\CategorieUtilisateur::tryFrom($user['categorie']);
                $validatorRole = $catEnum ? $catEnum->label() : $user['categorie'];
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
