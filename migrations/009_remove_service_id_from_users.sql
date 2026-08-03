-- user_services devient l'unique source de vérité des rattachements utilisateur-service.
UPDATE user_services us
JOIN users u ON u.id = us.user_id AND u.service_id IS NOT NULL
SET us.is_primary = 0;

INSERT INTO user_services (user_id, service_id, is_primary, is_responsable)
SELECT id, service_id, 1, 0
FROM users
WHERE service_id IS NOT NULL
ON DUPLICATE KEY UPDATE is_primary = 1;

ALTER TABLE users
    DROP FOREIGN KEY users_ibfk_1,
    DROP COLUMN service_id;
