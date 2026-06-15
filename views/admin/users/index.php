<div class="flex-between" style="margin-bottom: 24px;">
    <div></div>
    <a href="/admin/users/create" class="btn btn-filled">
        <span class="material-symbols-outlined">add</span>
        <?= __('new_user') ?>
    </a>
</div>

<div class="card" style="padding: 0;">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= __('fullname') ?></th>
                <th><?= __('email') ?></th>
                <th><?= __('category') ?></th>
                <th><?= __('status') ?></th>
                <th style="text-align: right;"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td style="font-weight: 500;"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                <td style="color: var(--md-sys-color-on-surface-variant);"><?= htmlspecialchars($user['email']) ?></td>
                <td><span style="font-size: 13px;"><?= htmlspecialchars($user['categorie']) ?></span></td>
                <td>
                    <?php if ($user['is_active']): ?>
                        <span style="color: #2E7D32; background: #E8F5E9; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 700;"><?= __('active') ?></span>
                    <?php else: ?>
                        <span style="color: #C62828; background: #FFEBEE; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 700;"><?= __('inactive') ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <a href="/admin/users/edit/<?= $user['id'] ?>" class="btn btn-text" title="<?= __('edit') ?>">
                        <span class="material-symbols-outlined">edit</span>
                    </a>
                    <a href="/admin/users/delete/<?= $user['id'] ?>" class="btn btn-text btn-danger" title="<?= __('delete') ?>" onclick="return confirm('<?= __('confirm_delete_user') ?>')">
                        <span class="material-symbols-outlined">delete</span>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
