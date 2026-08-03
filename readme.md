# GestFinance — Système de Gestion des Besoins Financiers

GestFinance est une application web de gestion des besoins financiers développée en PHP 8.3 pur, respectant les normes PSR et utilisant Material Design 3 pour l'interface utilisateur.

## 🚀 Fonctionnalités

- **Authentification sécurisée** : Connexion par email/mot de passe avec sessions régénérées.
- **Gestion Administrative** : CRUD complet pour les utilisateurs, services et rôles.
- **Workflow de Validation** : 
    - Création de demandes (Agent)
    - Validation multi-niveaux (Directeur -> DG -> Responsable Administratif)
    - Suivi du statut en temps réel avec badges colorés.
- **Génération de Fiche PDF** : Fiche officielle générée automatiquement après validation finale.
- **Sécurité** : Protection CSRF, Middleware d'authentification et de rôles, Limitation du taux de requêtes (Rate Limiting).

## 🛠 Stack Technique

- **Backend** : PHP 8.4 (Compatible 8.3+), Architecture MVC pure.
- **Base de données** : MySQL 8+ / MariaDB via PDO.
- **Frontend** : Esthétique Material Design 3 (via CSS personnalisé), Vanilla JS (ES2022+).
- **Standards** : PSR-1, PSR-2, PSR-4, PSR-7, PSR-12.
- **Dépendances** : Dompdf (PDF), PHP Dotenv (Environnement).

## 📋 Prérequis

- PHP 8.3 ou supérieur
- MySQL / MariaDB
- Composer

## 🔧 Installation

1. **Cloner le projet** :
   ```bash
   git clone <repository-url>
   cd gestfinance
   ```

2. **Installer les dépendances** :
   ```bash
   composer install
   ```

3. **Configuration** :
   - Copiez le fichier `.env.example` vers `.env`.
   - Modifiez les variables `DB_*` pour correspondre à votre base de données locale.
   - Créez la base de données spécifiée dans votre `.env`.

4. **Migration de la base de données** :
   ```bash
   composer migrate
   ```
   Cette commande applique dans l'ordre toutes les migrations encore en attente.
   Pour consulter leur état :
   ```bash
   composer migrate:status
   ```

5. **Lancer l'application** :
   Utilisez le serveur intégré de PHP pour le développement :
   ```bash
   php -S localhost:8000 -t public
   ```

## 👥 Comptes de Test (Exemple)

Vous pouvez insérer un administrateur par défaut via SQL :
```sql
INSERT INTO users (nom, prenom, email, password_hash, role_id, is_active)
SELECT 'Admin', 'Gest', 'admin@example.com',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       id, 1
FROM roles
WHERE code = 'dg';
-- Mot de passe : password
```

## 📂 Structure du Projet

- `app/` : Cœur de l'application (Controllers, Models, Services, Middleware, Enums, Core).
- `config/` : Fichiers de configuration.
- `public/` : Point d'entrée unique (`index.php`) et assets.
- `routes/` : Définition des routes web.
- `views/` : Templates HTML et Layouts.
- `migrations/` : Scripts SQL de création des tables.

## Convention des migrations

- Toute évolution de structure ou reprise de données doit être créée dans
  `migrations/` sous la forme `NNN_description_en_snake_case.sql`.
- Les migrations appliquées ne doivent jamais être modifiées : leur empreinte
  SHA-256 est contrôlée par le runner.
- `php migrate.php migrate` est l'unique commande autorisée pour modifier le
  schéma. Les anciens scripts `update_db.php` et `update_db_v2.php` ont été
  remplacés respectivement par les migrations `002` et `003`.
- `php migrate.php status` affiche les migrations appliquées, en attente ou
  enregistrées mais absentes du dépôt.

## Modèle d'autorisation

`roles.code` est la source de vérité pour les autorisations. Les seuls codes actifs
sont ceux de `CategorieUtilisateur` : `agent`, `responsable_directeur`, `dg`,
`responsable_administratif`, `responsable_administratif_adjoint` et `super_admin`.
`users.role_id` est une clé étrangère obligatoire vers ce référentiel. La table
`users` ne contient plus de copie du code rôle.

Un utilisateur peut appartenir à plusieurs services via `user_services`. Les
colonnes `is_primary` et `is_responsable` indiquent respectivement son service
principal et les services qu'il dirige. L'administration financière comprend
exactement deux fonctions distinctes : un `responsable_administratif` (chef) et
un `responsable_administratif_adjoint` (sous-chef). Un seul compte actif est
autorisé pour chacune de ces fonctions ; tous deux peuvent assurer la mise à
disposition et travaillent dans le même registre d'états.

La relation des responsables de service repose exclusivement sur
`user_services.is_responsable`. La table `services` ne contient plus de colonne
`responsable_id`, ce qui permet d'affecter plusieurs responsables à un service.

De la même manière, la table `users` ne contient plus de colonne `service_id`.
Tous les rattachements sont stockés dans `user_services` et le service principal
d'un utilisateur est celui dont `is_primary = 1`.
