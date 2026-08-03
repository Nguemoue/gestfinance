-- roles.code devient l'unique source de vérité des autorisations.
UPDATE users u
JOIN roles r ON r.code = u.categorie
SET u.role_id = r.id;

ALTER TABLE users
    DROP FOREIGN KEY users_ibfk_2,
    DROP COLUMN categorie,
    MODIFY role_id INT NOT NULL,
    ADD CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE RESTRICT;
