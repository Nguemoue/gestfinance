-- Distingue le chef administratif de son adjoint.
INSERT INTO roles (libelle, code, description, is_active)
VALUES (
    'Responsable Administratif Adjoint',
    'responsable_administratif_adjoint',
    'Sous-chef chargé de suppléer le responsable administratif',
    1
)
ON DUPLICATE KEY UPDATE
    libelle = VALUES(libelle),
    description = VALUES(description),
    is_active = 1;

-- Si deux RA existaient déjà avec l'ancien rôle, le second devient l'adjoint.
UPDATE users
SET role_id = (
        SELECT id FROM roles WHERE code = 'responsable_administratif_adjoint'
    ),
    categorie = 'responsable_administratif_adjoint'
WHERE id = (
    SELECT second_ra.id
    FROM (
        SELECT u.id
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE r.code = 'responsable_administratif'
        ORDER BY u.id
        LIMIT 1 OFFSET 1
    ) second_ra
);
