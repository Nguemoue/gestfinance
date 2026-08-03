<?php $title = __('etats'); ?>

<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<style>
    .etat-toolbar { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
    .etat-filters { display:grid; grid-template-columns:repeat(4,minmax(145px,1fr)); gap:10px; padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e3e8ef; }
    .etat-filter { width:100%; box-sizing:border-box; border:1px solid #c4c7c5; border-radius:8px; padding:9px 10px; background:#fff; }
    .etat-filter:focus { outline:2px solid color-mix(in srgb,var(--md-sys-color-primary) 20%,transparent); border-color:var(--md-sys-color-primary); }
    #etats-table_wrapper { padding:18px 20px; }
    #etats-table td { vertical-align:top; }
    .etat-object { max-width:280px; white-space:normal; }
    @media(max-width:900px) { .etat-filters { grid-template-columns:repeat(2,minmax(140px,1fr)); } }
    @media(max-width:520px) { .etat-filters { grid-template-columns:1fr; } }
</style>

<div class="card" style="margin-bottom:24px;">
    <div class="etat-toolbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="material-symbols-outlined" style="color:var(--md-sys-color-primary);font-size:32px;">assessment</span>
            <div>
                <h2 style="margin:0;font-size:22px;"><?= __('etats') ?></h2>
                <p style="margin:4px 0 0;color:var(--md-sys-color-outline);font-size:14px;">
                    Registre filtrable des demandes mises à disposition.
                </p>
            </div>
        </div>
        <button id="export-etats-pdf" type="button" class="btn btn-filled">
            <span class="material-symbols-outlined">picture_as_pdf</span>
            Exporter le résultat en PDF
        </button>
    </div>
</div>

<div class="card" style="padding:0;border-radius:20px;overflow:hidden;">
    <div class="etat-filters" aria-label="Filtres des états">
        <input class="etat-filter" id="filter-date-from" type="date" aria-label="Date minimale" title="Date minimale">
        <input class="etat-filter" id="filter-date-to" type="date" aria-label="Date maximale" title="Date maximale">
        <input class="etat-filter" id="filter-reference" type="search" placeholder="Référence">
        <input class="etat-filter" id="filter-beneficiaire" type="search" placeholder="Bénéficiaire">
        <input class="etat-filter" id="filter-service" type="search" placeholder="Service">
        <input class="etat-filter" id="filter-objet" type="search" placeholder="Objet">
        <input class="etat-filter" id="filter-montant-min" type="number" min="0" step="0.01" placeholder="Montant minimum">
        <input class="etat-filter" id="filter-montant-max" type="number" min="0" step="0.01" placeholder="Montant maximum">
        <select class="etat-filter" id="filter-justification">
            <option value="">Tous les statuts</option>
            <option value="1"><?= __('justified') ?></option>
            <option value="0"><?= __('not_justified') ?></option>
        </select>
        <button id="reset-etats-filters" type="button" class="btn btn-outlined">Réinitialiser les filtres</button>
    </div>

    <div style="overflow-x:auto;">
        <table id="etats-table" class="display" style="width:100%;">
            <thead>
                <tr>
                    <th><?= __('disposal_date') ?></th>
                    <th><?= __('ref_number') ?></th>
                    <th><?= __('beneficiary') ?></th>
                    <th>Service</th>
                    <th><?= __('request_object') ?></th>
                    <th><?= __('estimated_amount') ?></th>
                    <th><?= __('justification_status') ?></th>
                    <th><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demandes as $d): ?>
                    <?php
                        $beneficiaire = $d['beneficiaire_etat'] ?: trim($d['prenom'] . ' ' . $d['nom']);
                        $reference = $d['reference_etat']
                            ?: 'BF-' . date('Y', strtotime($d['created_at'])) . '-' . str_pad((string) $d['id'], 4, '0', STR_PAD_LEFT);
                        $date = strtotime((string) $d['date_mise_a_disposition']);
                    ?>
                    <tr>
                        <td data-order="<?= $date ?: 0 ?>"><?= $date ? htmlspecialchars(date('d/m/Y H:i', $date)) : '-' ?></td>
                        <td><?= htmlspecialchars($reference) ?></td>
                        <td><?= htmlspecialchars($beneficiaire) ?></td>
                        <td><?= htmlspecialchars($d['service_nom']) ?></td>
                        <td class="etat-object"><?= nl2br(htmlspecialchars($d['objet'])) ?></td>
                        <td data-order="<?= (float) $d['montant'] ?>"><?= htmlspecialchars(number_format((float) $d['montant'], 2, ',', ' ')) ?></td>
                        <td data-justified="<?= (int) $d['is_justified'] ?>">
                            <?= $d['is_justified'] ? __('justified') : __('not_justified') ?>
                        </td>
                        <td>
                            <a href="/demandes/<?= (int) $d['id'] ?>" class="btn btn-text" title="<?= __('details') ?>">
                                <span class="material-symbols-outlined">visibility</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.jQuery === 'undefined' || typeof window.DataTable === 'undefined') {
        console.error('DataTables n’a pas pu être chargé.');
        return;
    }

    const table = new DataTable('#etats-table', {
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']],
        columnDefs: [{targets: 7, orderable: false, searchable: false}],
        language: {
            search: 'Recherche globale :',
            lengthMenu: 'Afficher _MENU_ lignes',
            info: 'Affichage de _START_ à _END_ sur _TOTAL_ états',
            infoEmpty: 'Aucun état',
            zeroRecords: 'Aucun état ne correspond aux filtres',
            emptyTable: 'Aucun état disponible',
            paginate: {first: 'Premier', last: 'Dernier', next: 'Suivant', previous: 'Précédent'}
        }
    });

    const fields = {
        reference: document.querySelector('#filter-reference'),
        beneficiaire: document.querySelector('#filter-beneficiaire'),
        service: document.querySelector('#filter-service'),
        objet: document.querySelector('#filter-objet'),
        montantMin: document.querySelector('#filter-montant-min'),
        montantMax: document.querySelector('#filter-montant-max'),
        dateFrom: document.querySelector('#filter-date-from'),
        dateTo: document.querySelector('#filter-date-to'),
        justified: document.querySelector('#filter-justification')
    };

    DataTable.ext.search.push((settings, data, dataIndex) => {
        if (settings.nTable.id !== 'etats-table') return true;
        const row = table.row(dataIndex).node();
        const timestamp = Number(row.cells[0].dataset.order || 0) * 1000;
        const amount = Number(row.cells[5].dataset.order || 0);
        const from = fields.dateFrom.value ? new Date(fields.dateFrom.value + 'T00:00:00').getTime() : null;
        const to = fields.dateTo.value ? new Date(fields.dateTo.value + 'T23:59:59').getTime() : null;
        const min = fields.montantMin.value === '' ? null : Number(fields.montantMin.value);
        const max = fields.montantMax.value === '' ? null : Number(fields.montantMax.value);
        const justification = row.cells[6].dataset.justified;
        return (!from || timestamp >= from) && (!to || timestamp <= to)
            && (min === null || amount >= min) && (max === null || amount <= max)
            && (!fields.justified.value || justification === fields.justified.value);
    });

    const draw = () => {
        table.column(1).search(fields.reference.value);
        table.column(2).search(fields.beneficiaire.value);
        table.column(3).search(fields.service.value);
        table.column(4).search(fields.objet.value);
        table.draw();
    };
    Object.values(fields).forEach((field) => field.addEventListener('input', draw));
    fields.justified.addEventListener('change', draw);

    document.querySelector('#reset-etats-filters').addEventListener('click', () => {
        Object.values(fields).forEach((field) => { field.value = ''; });
        table.search('').columns().search('').draw();
    });

    document.querySelector('#export-etats-pdf').addEventListener('click', () => {
        const params = new URLSearchParams({
            search: table.search(),
            reference: fields.reference.value,
            beneficiaire: fields.beneficiaire.value,
            service: fields.service.value,
            objet: fields.objet.value,
            montant_min: fields.montantMin.value,
            montant_max: fields.montantMax.value,
            date_from: fields.dateFrom.value,
            date_to: fields.dateTo.value,
            is_justified: fields.justified.value
        });
        window.open('/etats/export-pdf?' + params.toString(), '_blank', 'noopener');
    });
});
</script>
