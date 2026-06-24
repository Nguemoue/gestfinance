<?php

require_once __DIR__ . '/vendor/autoload.php';


use App\Services\NotificationService;

NotificationService::sendEmail(
    toEmail: 'parfait.ngoum@confiance-app.com',
    toName: 'Test Notification',
    subject: 'This is a test notification.',
    htmlBody: 'This is a test notification.'
);
