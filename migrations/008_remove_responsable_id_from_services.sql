-- Transfère une dernière fois les responsables historiques vers user_services,
-- puis supprime définitivement services.responsable_id.
INSERT INTO user_services (user_id, service_id, is_primary, is_responsable)
SELECT responsable_id, id, 0, 1
FROM services
WHERE responsable_id IS NOT NULL
ON DUPLICATE KEY UPDATE is_responsable = 1;

ALTER TABLE services
    DROP FOREIGN KEY fk_services_responsable,
    DROP COLUMN responsable_id;
