<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="/admin/users/edit/<?= $user['id'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">

        <div class="flex gap-16">
            <div class="form-group" style="flex: 1;">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" class="form-control" value="<?= htmlspecialchars($user['nom']) ?>" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" class="form-control" value="<?= htmlspecialchars($user['prenom']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email professionnel</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe (laisser vide pour conserver l'actuel)</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Nouveau mot de passe">
        </div>

        <div class="flex gap-16">
            <div class="form-group" style="flex: 1;">
                <label for="service_ids">Services rattachés</label>
                <select id="service_ids" name="service_ids[]" class="form-control" multiple size="5">
                    <?php foreach ($services as $service): ?>
                        <option value="<?= $service['id'] ?>" <?= in_array((int) $service['id'], $selectedServiceIds, true) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($service['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="role_id">Rôle métier</label>
                <select id="role_id" name="role_id" class="form-control">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= $user['role_id'] == $role['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex gap-16">
            <div class="form-group" style="flex: 1;">
                <label>Catégorie utilisateur</label>
                <p style="margin: 8px 0; color: var(--md-sys-color-outline);">Déduite automatiquement du rôle sélectionné.</p>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="niveau_validation">Niveau de validation</label>
                <input type="number" id="niveau_validation" name="niveau_validation" class="form-control" value="<?= $user['niveau_validation'] ?>" min="0" max="3">
            </div>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 8px 0;">
                <input type="checkbox" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--md-sys-color-primary);">
                <span style="font-size: 16px;">Compte actif</span>
            </label>
        </div>

        <div class="flex gap-16 mt-24" style="border-top: 1px solid var(--md-sys-color-surface-variant); padding-top: 24px;">
            <button type="submit" class="btn btn-filled">
                <span class="material-symbols-outlined">done</span>
                Enregistrer les modifications
            </button>
            <a href="/admin/users" class="btn btn-outlined">Annuler</a>
        </div>
    </form>
</div>
