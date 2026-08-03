<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Middleware pour la protection CSRF.
 */
class CsrfMiddleware
{
    /**
     * Génère et stocke un token CSRF dans la session.
     */
    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifie si le token CSRF fourni est valide.
     */
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
            if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, (string) $token)) {
                http_response_code(403);
                die("Erreur CSRF : Token invalide ou manquant.");
            }
        }
    }
}
