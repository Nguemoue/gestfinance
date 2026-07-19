<?php
/**
 *  @var array $demande
 *  @var array $validations
 **/
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Fiche de Besoin Financier</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1565C0;
            padding-bottom: 10px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1565C0;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
            border: 1px solid #eee;
        }

        th {
            background-color: #f9f9f9;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">PAYMENT VOUCHER (Petty cash)</div>
        <div class="title">FICHE DE BESOIN FINANCIER</div>
        <div>Référence : BF-<?= date('Y') ?>-<?= str_pad((string) $demande['id'], 4, '0', STR_PAD_LEFT) ?></div>
    </div>

    <div class="section">
        <div class="section-title">INFORMATIONS DU DEMANDEUR</div>
        <table>
            <tr>
                <th>Nom & Prénom</th>
                <td><?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?></td>
                <th>Service</th>
                <td><?= htmlspecialchars($demande['service_nom']) ?></td>
            </tr>
            <tr>
                <th>Fonction</th>
                <td><?= htmlspecialchars($demande['fonction']) ?></td>
                <th>Date de création</th>
                <td><?= date('d/m/Y', strtotime($demande['created_at'])) ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">OBJET DE LA DEMANDE</div>
        <div style="padding: 10px; border: 1px solid #eee; min-height: 50px;">
            <?= nl2br(htmlspecialchars($demande['objet'])) ?>
        </div>
    </div>

    <div class="section">
        <div class="section-title">DÉTAILS FINANCIERS</div>
        <table>
            <tr>
                <th style="width: 30%;">Montant total</th>
                <td style="font-size: 14px; font-weight: bold;">
                    <?= number_format((float) $demande['montant'], 2, ',', ' ') ?> FCFA
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">CHAÎNE DE VALIDATION</div>
        <table>
            <thead>
                <tr>
                    <th>Étape</th>
                    <th>Valideur</th>
                    <th>Date</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($validations as $v): ?>
                    <tr>
                        <td>
                            <?php $etapeEnum = \App\Enums\EtapeValidation::tryFrom($v['etape']); ?>
                            <?= $etapeEnum ? htmlspecialchars($etapeEnum->label()) : ucfirst(htmlspecialchars($v['etape'])) ?>
                        </td>
                        <td><?= htmlspecialchars($v['prenom'] . ' ' . $v['nom']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
                        <td><?= htmlspecialchars($v['commentaire']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <br>
    <div>
        <span style="font-weight: bold; font-style: italic; font-size: 12px;"> Les pièces justificatives doivent être
            transmises au service financier dans un délai de 72 heures après utilisation des fonds. Passé ce délai, et
            en l’absence de pièces justificatives conformes, le montant concerné sera considéré comme une dette due par
            l’impétrant et devra être remboursé à Confiance. </span>
    </div>

    <div class="footer">
        Document généré le <?= date('d/m/Y H:i:s') ?> par GestFinance
    </div>
</body>

</html>