<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\NotificationService;
use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "Testing NotificationService loading...\n";

if (class_exists('App\Services\NotificationService')) {
    echo "✅ NotificationService class autoloaded successfully.\n";
} else {
    echo "❌ Failed to autoload NotificationService.\n";
}

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer class autoloaded successfully.\n";
} else {
    echo "❌ Failed to autoload PHPMailer.\n";
}

echo "Autoload check completed.\n";
