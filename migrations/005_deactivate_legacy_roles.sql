-- Les cinq codes issus de CategorieUtilisateur constituent désormais
-- l'unique référentiel de rôles actif.
UPDATE roles
SET is_active = 0
WHERE code NOT IN (
    'agent',
    'responsable_directeur',
    'dg',
    'responsable_administratif',
    'super_admin'
);
