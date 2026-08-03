<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form action="/admin/roles/edit/<?= $role['id'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">

        <div class="form-group">
            <label for="libelle">Libellé du Rôle</label>
            <input type="text" id="libelle" name="libelle" class="form-control" value="<?= htmlspecialchars($role['libelle']) ?>" required>
        </div>

        <div class="form-group">
            <label for="parent_id">Rôle Parent (Hiérarchie)</label>
            <select id="parent_id" name="parent_id" class="form-control">
                <option value="">-- Aucun (Rôle Racine) --</option>
                <?php foreach ($roles as $r): ?>
                    <?php if ($r['id'] != $role['id']): // Empêcher d'être parent de soi-même ?>
                        <option value="<?= $r['id'] ?>" <?= $role['parent_id'] == $r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['libelle']) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="code">Code métier</label>
            <select id="code" name="code" class="form-control" required>
                <?php foreach (\App\Enums\CategorieUtilisateur::cases() as $roleCode): ?>
                    <option value="<?= $roleCode->value ?>" <?= $role['code'] === $roleCode->value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($roleCode->label()) ?> — <?= $roleCode->value ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="description">Description du rôle</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 8px 0;">
                <input type="checkbox" name="is_active" <?= $role['is_active'] ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--md-sys-color-primary);">
                <span style="font-size: 16px;">Rôle actif</span>
            </label>
        </div>

        <div class="flex gap-16 mt-24" style="border-top: 1px solid var(--md-sys-color-surface-variant); padding-top: 24px;">
            <button type="submit" class="btn btn-filled">
                <span class="material-symbols-outlined">done</span>
                Enregistrer les modifications
            </button>
            <a href="/admin/roles" class="btn btn-outlined">Annuler</a>
        </div>
    </form>
</div>
