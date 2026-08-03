-- Les anciens rattachements pouvaient marquer comme responsable un utilisateur
-- dont le rôle n'est pas autorisé à gérer un service.
UPDATE user_services us
JOIN users u ON u.id = us.user_id
JOIN roles r ON r.id = u.role_id
SET us.is_responsable = 0
WHERE us.is_responsable = 1
  AND r.code NOT IN (
      'responsable_directeur',
      'responsable_administratif',
      'responsable_administratif_adjoint',
      'dg'
  );
