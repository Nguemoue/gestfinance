-- Instantané éditable utilisé par le registre des états.
ALTER TABLE demandes
    ADD COLUMN reference_etat VARCHAR(50) NULL AFTER is_justified,
    ADD COLUMN beneficiaire_etat VARCHAR(511) NULL AFTER reference_etat,
    ADD COLUMN date_mise_a_disposition DATETIME NULL AFTER beneficiaire_etat;

UPDATE demandes d
JOIN users u ON u.id = d.user_id
LEFT JOIN (
    SELECT demande_id, MAX(created_at) AS date_mise_a_disposition
    FROM validations
    WHERE action = 'validation'
      AND etape = 'responsable_administratif'
    GROUP BY demande_id
) v ON v.demande_id = d.id
SET d.reference_etat = CONCAT(
        'BF-',
        YEAR(COALESCE(d.created_at, CURRENT_TIMESTAMP)),
        '-',
        LPAD(d.id, 4, '0')
    ),
    d.beneficiaire_etat = TRIM(CONCAT(u.prenom, ' ', u.nom)),
    d.date_mise_a_disposition = v.date_mise_a_disposition
WHERE d.statut = 'mis_a_disposition';

CREATE UNIQUE INDEX uq_demandes_reference_etat ON demandes (reference_etat);
CREATE INDEX idx_demandes_date_mise_disposition ON demandes (date_mise_a_disposition);
