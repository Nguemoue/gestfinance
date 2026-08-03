<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modèle Utilisateur.
 */
class User extends Model
{
    protected string $table = 'users';

    /**
     * Trouve un utilisateur par son email.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.code AS role_code
             FROM {$this->table} u
             JOIN roles r ON r.id = u.role_id AND r.is_active = 1
             WHERE u.email = ? AND u.is_active = 1"
        );
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function allWithRoles(): array
    {
        $stmt = $this->db->query(
            "SELECT u.*, r.code AS role_code, r.libelle AS role_libelle
             FROM {$this->table} u
             JOIN roles r ON r.id = u.role_id
             ORDER BY u.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Crée un nouvel utilisateur.
     */
    public function create(array $data): bool
    {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
        return $stmt->execute(array_values($data));
    }

    public function countActiveByRoleCode(string $roleCode, ?int $exceptUserId = null): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} u JOIN roles r ON r.id = u.role_id
                WHERE u.is_active = 1 AND r.code = ?";
        $params = [$roleCode];
        if ($exceptUserId !== null) {
            $sql .= ' AND u.id <> ?';
            $params[] = $exceptUserId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Met à jour un utilisateur.
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        unset($data['password']);

        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
}
