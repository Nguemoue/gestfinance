<?php

/** @var \App\Core\Router $router */

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\ProfileController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\ServiceController;
use App\Controllers\Admin\RoleController;
use App\Controllers\User\DemandeController;
use App\Controllers\User\ValidationController;
use App\Controllers\User\FicheController;

// Landing & Portal
$router->get('/', [AuthController::class, 'landing']);

// Switch Language
$router->get('/lang/{locale}', function($locale) {
    if (in_array($locale, ['fr', 'en'])) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['lang'] = $locale;
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
    header("Location: $referer");
    exit;
});

// Auth System
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Profile
$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile', [ProfileController::class, 'update']);

// Shared Dashboard
$router->get('/dashboard', [HomeController::class, 'index']);

// Admin - Users
$router->get('/admin/users', [UserController::class, 'index']);
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users/create', [UserController::class, 'store']);
$router->get('/admin/users/edit/{id}', [UserController::class, 'edit']);
$router->post('/admin/users/edit/{id}', [UserController::class, 'update']);
$router->get('/admin/users/delete/{id}', [UserController::class, 'delete']);

// Admin - Services
$router->get('/admin/services', [ServiceController::class, 'index']);
$router->get('/admin/services/create', [ServiceController::class, 'create']);
$router->post('/admin/services/create', [ServiceController::class, 'store']);
$router->get('/admin/services/edit/{id}', [ServiceController::class, 'edit']);
$router->post('/admin/services/edit/{id}', [ServiceController::class, 'update']);
$router->get('/admin/services/delete/{id}', [ServiceController::class, 'delete']);

// Admin - Roles
$router->get('/admin/roles', [RoleController::class, 'index']);
$router->get('/admin/roles/create', [RoleController::class, 'create']);
$router->post('/admin/roles/create', [RoleController::class, 'store']);
$router->get('/admin/roles/edit/{id}', [RoleController::class, 'edit']);
$router->post('/admin/roles/edit/{id}', [RoleController::class, 'update']);
$router->get('/admin/roles/delete/{id}', [RoleController::class, 'delete']);

// User - Demandes
$router->get('/demandes', [DemandeController::class, 'index']);
$router->get('/demandes/create', [DemandeController::class, 'create']);
$router->post('/demandes/create', [DemandeController::class, 'store']);
$router->get('/demandes/{id}', [DemandeController::class, 'show']);

// User - Validations
$router->get('/validations', [ValidationController::class, 'index']);
$router->post('/validations/{id}/approve', [ValidationController::class, 'approve']);
$router->post('/validations/{id}/reject', [ValidationController::class, 'reject']);
$router->post('/validations/{id}/justify', [ValidationController::class, 'justify']);
$router->post('/validations/{id}/rollback', [ValidationController::class, 'rollback']);

// Fiches PDF
$router->get('/demandes/{id}/pdf', [FicheController::class, 'generate']);
