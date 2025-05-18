<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class MailerController extends AbstractController
{
    #[Route('/test-mail', name: 'app_test_mail')]
    public function index(
        MailerInterface $mailer,
        TransportInterface $transport,
        LoggerInterface $logger
    ): Response {
        $email = (new Email())
            ->from('test@example.com')
            ->to('test@demo.com')
            ->subject('Test Mailtrap avec Symfony 7')
            ->text('Ceci est un e-mail de test envoyé depuis Symfony 7 avec Mailtrap.')
            ->html('<p><strong>Ceci est un e-mail HTML</strong> envoyé depuis <em>Symfony 7</em> via Mailtrap.</p>');

        // Fonction pour extraire les adresses email proprement
        $extractAddresses = function(array $addresses): string {
            return implode(', ', array_map(fn($a) => $a->getAddress(), $addresses));
        };

        $logger->info('📤 Préparation de l\'envoi d\'un e-mail via Symfony Mailer.');
        $logger->info('Transport utilisé : ' . (string) $transport);
        $logger->info('De : ' . $extractAddresses($email->getFrom()));
        $logger->info('À : ' . $extractAddresses($email->getTo()));
        $logger->info('Sujet : ' . $email->getSubject());

        try {
            $mailer->send($email);
            $logger->info('✅ E-mail envoyé avec succès via Mailtrap.');
            $status = "<p style='color: green;'>✅ Email envoyé avec succès via Mailtrap !</p>";
        } catch (TransportExceptionInterface $e) {
            $logger->error('❌ Erreur lors de l\'envoi de l\'e-mail : ' . $e->getMessage());
            $status = "<p style='color: red;'>❌ Erreur lors de l'envoi : " . nl2br(htmlspecialchars($e->getMessage())) . "</p>";
        }

        $output = "<h2>🛠️ Informations de débogage</h2>";
        $output .= "<ul>";
        $output .= "<li><strong>Transport utilisé :</strong> " . htmlspecialchars((string) $transport) . "</li>";
        $output .= "<li><strong>From :</strong> " . htmlspecialchars($extractAddresses($email->getFrom())) . "</li>";
        $output .= "<li><strong>To :</strong> " . htmlspecialchars($extractAddresses($email->getTo())) . "</li>";
        $output .= "<li><strong>Subject :</strong> " . htmlspecialchars($email->getSubject()) . "</li>";
        $output .= "<li><strong>Texte brut :</strong> " . htmlspecialchars($email->getTextBody()) . "</li>";
        $output .= "<li><strong>Contenu HTML :</strong> " . $email->getHtmlBody() . "</li>";
        $output .= "</ul>" . $status;

        return new Response($output);
    }
}
