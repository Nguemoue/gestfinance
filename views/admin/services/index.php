<div class="flex-between" style="margin-bottom: 24px;">
    <div></div>
    <a href="/admin/services/create" class="btn btn-filled">
        <span class="material-symbols-outlined">add</span>
        <?= __('new_service') ?>
    </a>
</div>

<div class="card" style="padding: 0;">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= __('service_label') ?></th>
                <th><?= __('manager') ?></th>
                <th><?= __('code') ?></th>
                <th><?= __('status') ?></th>
                <th style="text-align: right;"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
            <tr>
                <td style="font-weight: 500;"><?= htmlspecialchars($service['libelle']) ?></td>
                <td>
                    <?php if ($service['responsables_noms']): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="material-symbols-outlined" style="font-size: 18px; color: var(--md-sys-color-primary);">account_circle</span>
                            <span style="font-size: 14px;"><?= htmlspecialchars($service['responsables_noms']) ?></span>
                        </div>
                    <?php else: ?>
                        <span style="color: var(--md-sys-color-outline); font-style: italic; font-size: 13px;"><?= __('unassigned') ?></span>
                    <?php endif; ?>
                </td>
                <td><code style="background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 12px;"><?= htmlspecialchars($service['code']) ?></code></td>
                <td>
                    <?php if ($service['is_active']): ?>
                        <span style="color: #2E7D32; background: #E8F5E9; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 700;"><?= __('active') ?></span>
                    <?php else: ?>
                        <span style="color: #C62828; background: #FFEBEE; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 700;"><?= __('inactive') ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <a href="/admin/services/edit/<?= $service['id'] ?>" class="btn btn-text" title="<?= __('edit') ?>">
                        <span class="material-symbols-outlined">edit</span>
                    </a>
                    <form action="/admin/services/delete/<?= $service['id'] ?>" method="POST" style="display:inline" onsubmit="return confirm('<?= __('confirm_delete_service') ?>')">
                        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">
                        <button type="submit" class="btn btn-text btn-danger" title="<?= __('delete') ?>"><span class="material-symbols-outlined">delete</span></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
