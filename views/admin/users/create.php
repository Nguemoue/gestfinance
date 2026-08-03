<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="/admin/users/create" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">

        <div class="flex gap-16">
            <div class="form-group" style="flex: 1;">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" class="form-control" placeholder="ex: KOUASSI" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" class="form-control" placeholder="ex: Jean" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email professionnel</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="jean.kouassi@entreprise.com" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 caractères" required>
        </div>

        <div class="flex gap-16">
            <div class="form-group" style="flex: 1;">
                <label for="service_ids">Services rattachés</label>
                <select id="service_ids" name="service_ids[]" class="form-control" multiple size="5">
                    <?php foreach ($services as $service): ?>
                        <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="role_id">Rôle métier</label>
                <select id="role_id" name="role_id" class="form-control">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['libelle']) ?></option>
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
                <label for="niveau_validation">Niveau de validation (0-3)</label>
                <input type="number" id="niveau_validation" name="niveau_validation" class="form-control" value="0" min="0" max="3">
            </div>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 8px 0;">
                <input type="checkbox" name="is_active" checked style="width: 20px; height: 20px; accent-color: var(--md-sys-color-primary);">
                <span style="font-size: 16px;">Activer le compte immédiatement</span>
            </label>
        </div>

        <div class="flex gap-16 mt-24" style="border-top: 1px solid var(--md-sys-color-surface-variant); padding-top: 24px;">
            <button type="submit" class="btn btn-filled">
                <span class="material-symbols-outlined">save</span>
                Créer l'utilisateur
            </button>
            <a href="/admin/users" class="btn btn-outlined">Annuler</a>
        </div>
    </form>
</div>
