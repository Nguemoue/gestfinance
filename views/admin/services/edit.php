<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form action="/admin/services/edit/<?= $service['id'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">

        <div class="form-group">
            <label for="libelle">Libellé du Service</label>
            <input type="text" id="libelle" name="libelle" class="form-control" value="<?= htmlspecialchars($service['libelle']) ?>" required>
        </div>

        <div class="form-group">
            <label for="code">Code (unique)</label>
            <input type="text" id="code" name="code" class="form-control" value="<?= htmlspecialchars($service['code']) ?>" required>
        </div>

        <div class="form-group">
            <label for="responsable_ids">Responsable(s) du Service</label>
            <select id="responsable_ids" name="responsable_ids[]" class="form-control" multiple size="6">
                <?php foreach ($users as $u): ?>
                    <?php $role = \App\Enums\CategorieUtilisateur::tryFrom((string) $u['role_code']); ?>
                    <option value="<?= (int) $u['id'] ?>" <?= in_array((int) $u['id'], $selectedResponsibleIds, true) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                        — <?= htmlspecialchars($role?->label() ?? $u['role_code']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="display:block; margin-top:6px; color:var(--md-sys-color-outline);">
                Maintenez Ctrl pour sélectionner plusieurs responsables.
            </small>
        </div>

        <div class="form-group">
            <label for="description">Description détaillée</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($service['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 8px 0;">
                <input type="checkbox" name="is_active" <?= $service['is_active'] ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--md-sys-color-primary);">
                <span style="font-size: 16px;">Service actif</span>
            </label>
        </div>

        <div class="flex gap-16 mt-24" style="border-top: 1px solid var(--md-sys-color-surface-variant); padding-top: 24px;">
            <button type="submit" class="btn btn-filled">
                <span class="material-symbols-outlined">done</span>
                Enregistrer les modifications
            </button>
            <a href="/admin/services" class="btn btn-outlined">Annuler</a>
        </div>
    </form>
</div>
