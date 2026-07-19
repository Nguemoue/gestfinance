<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modèle Demande.
 */
class Demande extends Model
{
    protected string $table = 'demandes';

    /**
     * Crée une nouvelle demande.
     */
    public function create(array $data): bool
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
        return $stmt->execute(array_values($data));
    }

    /**
     * Récupère une demande avec les informations du demandeur et du service.
     */
    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT d.*, u.nom, u.prenom, s.libelle as service_nom 
                                   FROM {$this->table} d 
                                   JOIN users u ON d.user_id = u.id 
                                   JOIN services s ON d.service_id = s.id 
                                   WHERE d.id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère les demandes pour un utilisateur spécifique.
     */
    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les statistiques globales pour le tableau de bord DG/Admin.
     */
    public function getGlobalStats(): array
    {
        $soumis = \App\Enums\StatutDemande::SOUMIS->value;
        $valideDir = \App\Enums\StatutDemande::VALIDE_DIRECTEUR->value;
        $valideDg = \App\Enums\StatutDemande::VALIDE_DG->value;
        $misADispo = \App\Enums\StatutDemande::MIS_A_DISPOSITION->value;

        $stmt = $this->db->query("
            SELECT 
                COUNT(id) as total_demandes,
                SUM(CASE WHEN statut = '{$soumis}' THEN 1 ELSE 0 END) as en_attente_dir,
                SUM(CASE WHEN statut = '{$valideDir}' THEN 1 ELSE 0 END) as en_attente_dg,
                SUM(CASE WHEN statut = '{$valideDg}' THEN 1 ELSE 0 END) as en_attente_ra,
                SUM(CASE WHEN statut = '{$misADispo}' THEN 1 ELSE 0 END) as validees,
                SUM(CASE WHEN statut = '{$misADispo}' THEN montant ELSE 0 END) as budget_consomme
            FROM {$this->table}
        ");
        return $stmt->fetch() ?: [
            'total_demandes' => 0, 'en_attente_dir' => 0, 'en_attente_dg' => 0, 
            'en_attente_ra' => 0, 'validees' => 0, 'budget_consomme' => 0
        ];
    }
}
