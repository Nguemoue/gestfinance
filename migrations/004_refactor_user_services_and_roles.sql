-- Standardise les rôles et introduit la relation plusieurs-à-plusieurs
-- entre les utilisateurs et les services.

INSERT INTO roles (libelle, code, description, is_active)
VALUES
    ('Agent', 'agent', 'Utilisateur demandeur', 1),
    ('Responsable / Directeur', 'responsable_directeur', 'Responsable d''un ou plusieurs services', 1),
    ('Directeur Général', 'dg', 'Approbateur de niveau direction générale', 1),
    ('Responsable Administratif', 'responsable_administratif', 'Responsable de la mise à disposition et des justificatifs', 1),
    ('Super Administrateur', 'super_admin', 'Administration technique complète', 1)
ON DUPLICATE KEY UPDATE
    libelle = VALUES(libelle),
    description = VALUES(description),
    is_active = 1;

UPDATE users u
JOIN roles r ON r.code = LOWER(u.categorie)
SET u.role_id = r.id
WHERE u.role_id IS NULL
   OR NOT EXISTS (
       SELECT 1 FROM roles current_role
       WHERE current_role.id = u.role_id
         AND current_role.code = LOWER(u.categorie)
   );

CREATE TABLE IF NOT EXISTS user_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    is_responsable BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_services UNIQUE (user_id, service_id),
    CONSTRAINT fk_user_services_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_services_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_user_services_service_responsable (service_id, is_responsable),
    INDEX idx_user_services_user_primary (user_id, is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO user_services (user_id, service_id, is_primary, is_responsable)
SELECT u.id, u.service_id, 1,
       CASE WHEN s.responsable_id = u.id THEN 1 ELSE 0 END
FROM users u
LEFT JOIN services s ON s.id = u.service_id
WHERE u.service_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    is_primary = VALUES(is_primary),
    is_responsable = GREATEST(is_responsable, VALUES(is_responsable));

INSERT INTO user_services (user_id, service_id, is_primary, is_responsable)
SELECT s.responsable_id, s.id, 0, 1
FROM services s
WHERE s.responsable_id IS NOT NULL
ON DUPLICATE KEY UPDATE is_responsable = 1;

ALTER TABLE demandes ALTER statut SET DEFAULT 'brouillon';
CREATE INDEX idx_demandes_workflow ON demandes (statut, service_id, updated_at);
CREATE INDEX idx_demandes_justification ON demandes (statut, is_justified, updated_at);
CREATE INDEX idx_validations_lookup ON validations (demande_id, action, etape, validateur_id);
