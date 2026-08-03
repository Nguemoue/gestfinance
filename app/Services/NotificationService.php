<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Demande;
use App\Enums\StatutDemande;
use App\Enums\CategorieUtilisateur;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Dotenv\Dotenv;

class NotificationService
{
    /**
     * Envoie un e-mail via SMTP en utilisant PHPMailer.
     */
    public static function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Charger .env si non défini
            if (!isset($_ENV['SMTP_HOST'])) {
                $dotenvPath = dirname(__DIR__, 2) . '/';
                if (file_exists($dotenvPath . '/.env')) {
                    $dotenv = Dotenv::createImmutable($dotenvPath);
                    $dotenv->load();
                }
            }

            // Configuration du serveur SMTP
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'] ?? '127.0.0.1';

            // SMTPAuth est vrai si un nom d'utilisateur est fourni
            $username = $_ENV['SMTP_USERNAME'] ?? 'null';
            $mail->SMTPAuth = !empty($username) && $username !== 'null';
            if ($mail->SMTPAuth) {
                $mail->Username = $username;
                $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
            }

            $encryption = $_ENV['SMTP_ENCRYPTION'] ?? 'null';
            if (!empty($encryption) && $encryption !== 'null') {
                $mail->SMTPSecure = $encryption; // 'tls' ou 'ssl'
            }

            $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 1025);
            $mail->CharSet = 'UTF-8';

            // Expéditeur et destinataire
            $fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@gestfinance.com';
            $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'GestFinance';
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($toEmail, $toName);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            $mail->send();
            return true;
        } catch (\Exception $e) {
            //var_dump($e->getMessage());
            // Enregistrement de l'erreur dans les logs PHP pour ne pas bloquer l'application
            error_log("NotificationService Error sending email to $toEmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifie la création d'une demande.
     */
    public static function notifyCreation(int $demandeId): void
    {
        $db = Database::getInstance();
        $demandeModel = new Demande();
        $demande = $demandeModel->findWithDetails($demandeId);
        if (!$demande) {
            return;
        }

        $creatorId = (int) $demande['user_id'];
        $stmt = $db->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$creatorId]);
        $creator = $stmt->fetch();
        if (!$creator) {
            return;
        }

        $creatorName = $creator['prenom'] . ' ' . $creator['nom'];
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $link = $appUrl . "/demandes/" . $demandeId;
        $formattedAmount = number_format((float) $demande['montant'], 2, ',', ' ');
        $refId = str_pad((string) $demandeId, 4, '0', STR_PAD_LEFT);
        $year = date('Y');

        $detailsBox = self::buildDetailsBox($creatorName, $demande['service_nom'], $demande['objet'], $formattedAmount, $refId, $year);

        // 1. Notification au créateur
        $creatorSubject = "Votre demande de besoin financier a été enregistrée - BF-{$year}-{$refId}";
        $creatorHtml = self::buildEmailHtml(
            $creatorSubject,
            "Confirmation d'enregistrement",
            "Bonjour " . htmlspecialchars($creator['prenom']) . ",",
            "Votre demande de besoin financier a bien été enregistrée dans notre système avec le statut <strong>" . htmlspecialchars(StatutDemande::from($demande['statut'])->label()) . "</strong>.<br>Vous recevrez une notification par e-mail à chaque étape de sa validation.",
            $detailsBox,
            $link,
            $year
        );
        self::sendEmail($creator['email'], $creatorName, $creatorSubject, $creatorHtml);

        // 2. Déterminer le destinataire suivant
        if ($demande['statut'] === StatutDemande::SOUMIS->value) {
            // Notification au Responsable de Service
            $stmtService = $db->prepare("
                SELECT DISTINCT u.email, u.nom, u.prenom
                FROM user_services us
                JOIN users u ON u.id = us.user_id
                JOIN roles r ON r.id = u.role_id
                WHERE us.service_id = ?
                  AND us.is_responsable = 1
                  AND u.is_active = 1
                  AND r.is_active = 1
                  AND r.code IN (
                      'responsable_directeur',
                      'responsable_administratif',
                      'responsable_administratif_adjoint',
                      'dg'
                  )
                ORDER BY u.nom, u.prenom
            ");
            $stmtService->execute([$demande['service_id']]);
            $responsibles = $stmtService->fetchAll();

            foreach ($responsibles as $responsible) {
                $respName = $responsible['prenom'] . ' ' . $responsible['nom'];
                $respSubject = "Nouvelle demande de besoin financier à valider - BF-{$year}-{$refId}";
                $respLink = $appUrl . "/validations";
                $respHtml = self::buildEmailHtml(
                    $respSubject,
                    "Nouvelle demande en attente de validation",
                    "Bonjour " . htmlspecialchars($responsible['prenom']) . ",",
                    "Une nouvelle demande de besoin financier a été soumise par <strong>" . htmlspecialchars($creatorName) . "</strong> pour votre service.<br>Merci de vous connecter pour valider ou rejeter cette demande.",
                    $detailsBox,
                    $respLink,
                    $year
                );
                self::sendEmail($responsible['email'], $respName, $respSubject, $respHtml);
            }
        } elseif ($demande['statut'] === StatutDemande::VALIDE_DIRECTEUR->value) {
            // Notification au DG
            self::notifyGroup(
                CategorieUtilisateur::DG->value,
                "Demande de besoin financier en attente d'approbation DG - BF-{$year}-{$refId}",
                "Demande en attente d'approbation DG",
                "Bonjour,",
                "Une demande de besoin financier soumise par <strong>" . htmlspecialchars($creatorName) . "</strong> a été validée par son responsable de service et attend votre approbation finale.",
                $detailsBox,
                $appUrl . "/validations",
                $year
            );
        } elseif ($demande['statut'] === StatutDemande::VALIDE_DG->value) {
            // Notification au RA
            self::notifyAdministrativeManagers(
                "Demande de besoin financier approuvée en attente de traitement - BF-{$year}-{$refId}",
                "Demande en attente de mise à disposition",
                "Bonjour,",
                "Une demande de besoin financier approuvée par la Direction Générale pour <strong>" . htmlspecialchars($creatorName) . "</strong> est en attente de mise à disposition des fonds.",
                $detailsBox,
                $appUrl . "/validations",
                $year
            );
        }
    }

    /**
     * Notifie la validation d'une demande.
     */
    public static function notifyValidation(int $demandeId, string $validatorName, string $validatorRole, string $newStatus, string $commentaire): void
    {
        $db = Database::getInstance();
        $demandeModel = new Demande();
        $demande = $demandeModel->findWithDetails($demandeId);
        if (!$demande) {
            return;
        }

        $creatorId = (int) $demande['user_id'];
        $stmt = $db->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$creatorId]);
        $creator = $stmt->fetch();
        if (!$creator) {
            return;
        }

        $creatorName = $creator['prenom'] . ' ' . $creator['nom'];
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $link = $appUrl . "/demandes/" . $demandeId;
        $formattedAmount = number_format((float) $demande['montant'], 2, ',', ' ');
        $refId = str_pad((string) $demandeId, 4, '0', STR_PAD_LEFT);
        $year = date('Y');

        $detailsBox = self::buildDetailsBox($creatorName, $demande['service_nom'], $demande['objet'], $formattedAmount, $refId, $year);
        $commentHtml = !empty($commentaire) ? "<br><br><strong>Commentaire du valideur :</strong><br><em>\"" . htmlspecialchars($commentaire) . "\"</em>" : "";

        // 1. Notification au créateur
        $creatorSubject = "Mise à jour de votre demande - BF-{$year}-{$refId}";
        $statusLabel = StatutDemande::from($newStatus)->label();

        $creatorMessage = "Votre demande de besoin financier a été validée par <strong>" . htmlspecialchars($validatorName) . "</strong> (" . htmlspecialchars($validatorRole) . ").<br>Le nouveau statut est : <strong>" . htmlspecialchars($statusLabel) . "</strong>." . $commentHtml;

        $creatorHtml = self::buildEmailHtml(
            $creatorSubject,
            "Mise à jour de statut",
            "Bonjour " . htmlspecialchars($creator['prenom']) . ",",
            $creatorMessage,
            $detailsBox,
            $link,
            $year
        );
        self::sendEmail($creator['email'], $creatorName, $creatorSubject, $creatorHtml);

        // 2. Notification de l'étape suivante
        if ($newStatus === StatutDemande::VALIDE_DIRECTEUR->value) {
            self::notifyGroup(
                CategorieUtilisateur::DG->value,
                "Demande de besoin financier en attente d'approbation DG - BF-{$year}-{$refId}",
                "Demande en attente d'approbation DG",
                "Bonjour,",
                "La demande de <strong>" . htmlspecialchars($creatorName) . "</strong> a été validée par " . htmlspecialchars($validatorName) . " (" . htmlspecialchars($validatorRole) . ") et attend votre approbation." . $commentHtml,
                $detailsBox,
                $appUrl . "/validations",
                $year
            );
        } elseif ($newStatus === StatutDemande::VALIDE_DG->value) {
            self::notifyAdministrativeManagers(
                "Demande de besoin financier approuvée en attente de traitement - BF-{$year}-{$refId}",
                "Demande en attente de mise à disposition",
                "Bonjour,",
                "La demande de <strong>" . htmlspecialchars($creatorName) . "</strong> a été approuvée par le DG " . htmlspecialchars($validatorName) . " et attend la mise à disposition des fonds." . $commentHtml,
                $detailsBox,
                $appUrl . "/validations",
                $year
            );
        }
    }

    /**
     * Notifie le rejet d'une demande.
     */
    public static function notifyRejection(int $demandeId, string $validatorName, string $validatorRole, string $commentaire): void
    {
        $db = Database::getInstance();
        $demandeModel = new Demande();
        $demande = $demandeModel->findWithDetails($demandeId);
        if (!$demande) {
            return;
        }

        $creatorId = (int) $demande['user_id'];
        $stmt = $db->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$creatorId]);
        $creator = $stmt->fetch();
        if (!$creator) {
            return;
        }

        $creatorName = $creator['prenom'] . ' ' . $creator['nom'];
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $link = $appUrl . "/demandes/" . $demandeId;
        $formattedAmount = number_format((float) $demande['montant'], 2, ',', ' ');
        $refId = str_pad((string) $demandeId, 4, '0', STR_PAD_LEFT);
        $year = date('Y');

        $detailsBox = self::buildDetailsBox($creatorName, $demande['service_nom'], $demande['objet'], $formattedAmount, $refId, $year);

        $creatorSubject = "Rejet de votre demande de besoin financier - BF-{$year}-{$refId}";
        $creatorMessage = "Nous vous informons que votre demande de besoin financier a été <strong>rejetée</strong> par <strong>" . htmlspecialchars($validatorName) . "</strong> (" . htmlspecialchars($validatorRole) . ").<br><br><strong>Motif / Commentaire :</strong><br><em>\"" . htmlspecialchars($commentaire) . "\"</em>";

        $creatorHtml = self::buildEmailHtml(
            $creatorSubject,
            "Demande Rejetée",
            "Bonjour " . htmlspecialchars($creator['prenom']) . ",",
            $creatorMessage,
            $detailsBox,
            $link,
            $year,
            '#BA1A1A'
        );
        self::sendEmail($creator['email'], $creatorName, $creatorSubject, $creatorHtml);
    }

    /**
     * Notifie la validation de la justification.
     */
    public static function notifyJustification(int $demandeId, string $raName): void
    {
        $db = Database::getInstance();
        $demandeModel = new Demande();
        $demande = $demandeModel->findWithDetails($demandeId);
        if (!$demande) {
            return;
        }

        $creatorId = (int) $demande['user_id'];
        $stmt = $db->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$creatorId]);
        $creator = $stmt->fetch();
        if (!$creator) {
            return;
        }

        $creatorName = $creator['prenom'] . ' ' . $creator['nom'];
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $link = $appUrl . "/demandes/" . $demandeId;
        $formattedAmount = number_format((float) $demande['montant'], 2, ',', ' ');
        $refId = str_pad((string) $demandeId, 4, '0', STR_PAD_LEFT);
        $year = date('Y');

        $detailsBox = self::buildDetailsBox($creatorName, $demande['service_nom'], $demande['objet'], $formattedAmount, $refId, $year);

        $creatorSubject = "Justification validée - BF-{$year}-{$refId}";
        $creatorMessage = "Le justificatif de votre demande de besoin financier a été <strong>validé</strong> par le Responsable Administratif (<strong>" . htmlspecialchars($raName) . "</strong>).";

        $creatorHtml = self::buildEmailHtml(
            $creatorSubject,
            "Justification validée",
            "Bonjour " . htmlspecialchars($creator['prenom']) . ",",
            $creatorMessage,
            $detailsBox,
            $link,
            $year,
            '#2E7D32'
        );
        self::sendEmail($creator['email'], $creatorName, $creatorSubject, $creatorHtml);
    }

    /**
     * Notifie le rejet/l'annulation d'une justification.
     */
    public static function notifyRollbackJustification(int $demandeId, string $raName): void
    {
        $db = Database::getInstance();
        $demandeModel = new Demande();
        $demande = $demandeModel->findWithDetails($demandeId);
        if (!$demande) {
            return;
        }

        $creatorId = (int) $demande['user_id'];
        $stmt = $db->prepare("SELECT email, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$creatorId]);
        $creator = $stmt->fetch();
        if (!$creator) {
            return;
        }

        $creatorName = $creator['prenom'] . ' ' . $creator['nom'];
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        $link = $appUrl . "/demandes/" . $demandeId;
        $formattedAmount = number_format((float) $demande['montant'], 2, ',', ' ');
        $refId = str_pad((string) $demandeId, 4, '0', STR_PAD_LEFT);
        $year = date('Y');

        $detailsBox = self::buildDetailsBox($creatorName, $demande['service_nom'], $demande['objet'], $formattedAmount, $refId, $year);

        $creatorSubject = "Action requise : Justification rejetée - BF-{$year}-{$refId}";
        $creatorMessage = "La justification précédemment fournie pour votre demande a été <strong>rejetée ou annulée</strong> par le Responsable Administratif (<strong>" . htmlspecialchars($raName) . "</strong>).<br>Veuillez fournir les justificatifs requis ou contacter l'administration.";

        $creatorHtml = self::buildEmailHtml(
            $creatorSubject,
            "Justification Rejetée",
            "Bonjour " . htmlspecialchars($creator['prenom']) . ",",
            $creatorMessage,
            $detailsBox,
            $link,
            $year,
            '#BA1A1A'
        );
        self::sendEmail($creator['email'], $creatorName, $creatorSubject, $creatorHtml);
    }

    /**
     * Envoie le même e-mail à tous les membres d'une catégorie d'utilisateurs.
     */
    private static function notifyGroup(string $category, string $subject, string $subtitle, string $salutation, string $message, string $detailsBox, string $link, string $year): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.email, u.nom, u.prenom
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.code = ? AND u.is_active = 1 AND r.is_active = 1"
        );
        $stmt->execute([$category]);
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            $name = $user['prenom'] . ' ' . $user['nom'];
            $html = self::buildEmailHtml(
                $subject,
                $subtitle,
                "Bonjour " . htmlspecialchars($user['prenom']) . ",",
                $message,
                $detailsBox,
                $link,
                $year
            );
            self::sendEmail($user['email'], $name, $subject, $html);
        }
    }

    private static function notifyAdministrativeManagers(
        string $subject,
        string $subtitle,
        string $salutation,
        string $message,
        string $detailsBox,
        string $link,
        string $year
    ): void {
        foreach ([
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF->value,
            CategorieUtilisateur::RESPONSABLE_ADMINISTRATIF_ADJOINT->value,
        ] as $category) {
            self::notifyGroup(
                $category,
                $subject,
                $subtitle,
                $salutation,
                $message,
                $detailsBox,
                $link,
                $year
            );
        }
    }

    /**
     * Construit le conteneur HTML des informations de la demande.
     */
    private static function buildDetailsBox(string $requester, string $service, string $object, string $amount, string $refId, string $year): string
    {
        return '
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F1F3F9; border-radius: 12px; padding: 20px; margin-bottom: 32px; border: 1px solid #E0E2EC;">
            <tr>
                <td style="padding-bottom: 12px; font-size: 14px; color: #74777F; width: 40%;"><strong>Référence :</strong></td>
                <td style="padding-bottom: 12px; font-size: 14px; color: #1A1C1E; font-family: monospace; font-weight: bold;">BF-' . $year . '-' . $refId . '</td>
            </tr>
            <tr>
                <td style="padding-bottom: 12px; font-size: 14px; color: #74777F;"><strong>Demandeur :</strong></td>
                <td style="padding-bottom: 12px; font-size: 14px; color: #1A1C1E;">' . htmlspecialchars($requester) . '</td>
            </tr>
            <tr>
                <td style="padding-bottom: 12px; font-size: 14px; color: #74777F;"><strong>Service Bénéficiaire :</strong></td>
                <td style="padding-bottom: 12px; font-size: 14px; color: #1A1C1E;">' . htmlspecialchars($service) . '</td>
            </tr>
            <tr>
                <td style="padding-bottom: 12px; font-size: 14px; color: #74777F; vertical-align: top;"><strong>Objet :</strong></td>
                <td style="padding-bottom: 12px; font-size: 14px; color: #1A1C1E; line-height: 1.4;">' . nl2br(htmlspecialchars($object)) . '</td>
            </tr>
            <tr>
                <td style="font-size: 14px; color: #74777F;"><strong>Montant :</strong></td>
                <td style="font-size: 16px; color: #0061A4; font-weight: bold;">' . $amount . ' FCFA</td>
            </tr>
        </table>';
    }

    /**
     * Construit le gabarit HTML premium complet de l'e-mail.
     */
    private static function buildEmailHtml(string $subject, string $subtitle, string $salutation, string $message, string $detailsBox, string $link, string $year, string $themeColor = '#0061A4'): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($subject) . '</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #F8F9FA; font-family: \'Roboto\', \'Helvetica Neue\', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1A1C1E;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8F9FA; padding: 20px 0;">
                <tr>
                    <td align="center">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #E0E2EC;">
                            <!-- Header -->
                            <tr>
                                <td align="center" style="background-color: ' . $themeColor . '; padding: 32px 20px; color: #FFFFFF;">
                                    <h1 style="margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;">GestFinance</h1>
                                    <p style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.85;">' . htmlspecialchars($subtitle) . '</p>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 32px;">
                                    <h2 style="margin: 0 0 20px 0; font-size: 20px; font-weight: 700; color: ' . $themeColor . ';">' . htmlspecialchars($salutation) . '</h2>
                                    <div style="font-size: 16px; line-height: 1.6; color: #44474E; margin-bottom: 32px;">
                                        ' . $message . '
                                    </div>
                                    
                                    ' . $detailsBox . '
                                    
                                    <div style="text-align: center; margin-top: 32px;">
                                        <a href="' . htmlspecialchars($link) . '" style="background-color: ' . $themeColor . '; color: #FFFFFF; text-decoration: none; padding: 12px 32px; border-radius: 100px; font-weight: 500; font-size: 14px; display: inline-block; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);">Voir la demande</a>
                                    </div>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td align="center" style="background-color: #F1F3F9; padding: 24px 20px; font-size: 12px; color: #74777F; border-top: 1px solid #E0E2EC;">
                                    <p style="margin: 0 0 8px 0;">Cet e-mail a été généré automatiquement par GestFinance.</p>
                                    <p style="margin: 0;">&copy; ' . $year . ' GestFinance. Tous droits réservés.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
}
