<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Enums\CategorieUtilisateur;

final class UserService
{
    public static function serviceIdsForUser(int $userId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT service_id FROM user_services WHERE user_id = ? ORDER BY is_primary DESC, service_id'
        );
        $stmt->execute([$userId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function primaryServiceIdForUser(int $userId): ?int
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT service_id
             FROM user_services
             WHERE user_id = ?
             ORDER BY is_primary DESC, service_id
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $serviceId = $stmt->fetchColumn();
        return $serviceId === false ? null : (int) $serviceId;
    }

    public static function primaryServiceForUser(int $userId): ?array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT s.*
             FROM services s
             JOIN user_services us ON us.service_id = s.id
             WHERE us.user_id = ?
             ORDER BY us.is_primary DESC, s.id
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public static function isResponsibleFor(int $userId, int $serviceId): bool
    {
        $rolePlaceholders = implode(',', array_fill(0, count(CategorieUtilisateur::serviceManagerCodes()), '?'));
        $stmt = Database::getInstance()->prepare(
            "SELECT 1
             FROM user_services us
             JOIN users u ON u.id = us.user_id
             JOIN roles r ON r.id = u.role_id
             WHERE us.user_id = ?
               AND us.service_id = ?
               AND us.is_responsable = 1
               AND u.is_active = 1
               AND r.is_active = 1
               AND r.code IN ($rolePlaceholders)"
        );
        $stmt->execute([$userId, $serviceId, ...CategorieUtilisateur::serviceManagerCodes()]);
        return (bool) $stmt->fetchColumn();
    }

    public static function sync(int $userId, array $serviceIds, ?int $primaryServiceId, ?array $responsibleServiceIds = null): void
    {
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));
        if ($responsibleServiceIds === null) {
            $stmt = Database::getInstance()->prepare(
                'SELECT service_id FROM user_services WHERE user_id = ? AND is_responsable = 1'
            );
            $stmt->execute([$userId]);
            $responsibleServiceIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        $responsibleServiceIds = array_values(array_unique(array_filter(array_map('intval', $responsibleServiceIds))));
        $serviceIds = array_values(array_unique([...$serviceIds, ...$responsibleServiceIds]));

        if ($primaryServiceId !== null && !in_array($primaryServiceId, $serviceIds, true)) {
            $serviceIds[] = $primaryServiceId;
        }

        $db = Database::getInstance();
        $db->prepare('DELETE FROM user_services WHERE user_id = ?')->execute([$userId]);
        $insert = $db->prepare(
            'INSERT INTO user_services (user_id, service_id, is_primary, is_responsable) VALUES (?, ?, ?, ?)'
        );
        foreach ($serviceIds as $serviceId) {
            $insert->execute([
                $userId,
                $serviceId,
                $serviceId === $primaryServiceId ? 1 : 0,
                in_array($serviceId, $responsibleServiceIds, true) ? 1 : 0,
            ]);
        }
    }
}
