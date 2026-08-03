<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 24px 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color:#1f2937; font-size:9px; }
        h1 { margin:0 0 4px; color:#064f83; font-size:19px; }
        .meta { color:#64748b; margin-bottom:12px; }
        .filters { margin:0 0 12px; padding:8px 10px; background:#eef6fc; border:1px solid #cfe4f4; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th { background:#075985; color:#fff; padding:7px 5px; text-align:left; font-size:8px; }
        td { border-bottom:1px solid #dbe2e8; padding:6px 5px; vertical-align:top; word-wrap:break-word; }
        tr:nth-child(even) td { background:#f8fafc; }
        .amount { text-align:right; }
        .empty { padding:24px; text-align:center; color:#64748b; }
        .footer { position:fixed; bottom:-22px; left:0; right:0; color:#64748b; font-size:8px; text-align:right; }
    </style>
</head>
<body>
    <div class="footer">GestFinance - Situation de mise a disposition</div>
    <h1 style="text-align: center;font-size: 24px">Situation de mise a disposition</h1>
    <div class="meta">
        Periode du <?= htmlspecialchars($generatedAt->format('d/m/Y')) ?> -
        <div style="text-align: right">
            <?= count($demandes) ?> résultat(s)
        </div>

    </div>
    <?php if ($filters): ?>
        <div class="filters">
            <strong>Filtres appliqués :</strong>
            <?php foreach ($filters as $name => $value): ?>
                <?= htmlspecialchars($name) ?> = <?= htmlspecialchars($value) ?>&nbsp;&nbsp;
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th style="width:12%;">Date</th>
                <th style="width:11%;">Référence</th>
                <th style="width:15%;">Bénéficiaire</th>
                <th style="width:12%;">Service</th>
                <th style="width:25%;">Objet</th>
                <th style="width:12%;">Montant</th>
                <th style="width:13%;">Justification</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$demandes): ?>
                <tr><td colspan="7" class="empty">Aucun état ne correspond aux filtres.</td></tr>
            <?php endif; ?>
            <?php foreach ($demandes as $d): ?>
                <?php
                    $beneficiaire = $d['beneficiaire_etat'] ?: trim($d['prenom'] . ' ' . $d['nom']);
                    $reference = $d['reference_etat']
                        ?: 'BF-' . date('Y', strtotime($d['created_at'])) . '-' . str_pad((string) $d['id'], 4, '0', STR_PAD_LEFT);
                ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['date_mise_a_disposition']))) ?></td>
                    <td><?= htmlspecialchars($reference) ?></td>
                    <td><?= htmlspecialchars($beneficiaire) ?></td>
                    <td><?= htmlspecialchars($d['service_nom']) ?></td>
                    <td><?= nl2br(htmlspecialchars($d['objet'])) ?></td>
                    <td class="amount"><?= htmlspecialchars(number_format((float) $d['montant'], 2, ',', ' ')) ?></td>
                    <td><?= $d['is_justified'] ? 'Justifiée' : 'Non justifiée' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
