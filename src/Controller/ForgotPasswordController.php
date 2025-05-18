<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ForgotPasswordController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        $successMessage = null;
        $infoMessage = null;
        $errorMessage = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $emailInput = $form->get('email')->getData();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $emailInput]);

            if ($user) {
                try {
                    // Génère un token unique et une date d'expiration (30 min)
                    $token = Uuid::v4();
                    $user->setResetToken($token);
                    $user->setResetTokenExpiresAt((new \DateTime())->modify('+30 minutes'));
                    $em->flush();

                    // Prépare et envoie l'e-mail
                    $email = (new TemplatedEmail())
                        ->from(new Address('no-reply@example.com', 'Support'))
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe')
                        ->htmlTemplate('emails/reset_password.html.twig')
                        ->context([
                            'resetUrl' => $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                            'expiresAt' => $user->getResetTokenExpiresAt(),
                        ]);

                    $mailer->send($email);
                    $successMessage = 'Un e-mail de réinitialisation a été envoyé.';
                } catch (TransportExceptionInterface $e) {
                    $errorMessage = 'Une erreur est survenue lors de l\'envoi de l\'e-mail : ' . $e->getMessage();
                }
            } else {
                $infoMessage = 'Si un compte existe avec cette adresse, vous recevrez un e-mail.';
            }
        }

        return $this->render('forgot_password/index.html.twig', [
            'form' => $form->createView(),
            'successMessage' => $successMessage,
            'infoMessage' => $infoMessage,
            'errorMessage' => $errorMessage,
        ]);
    }
}
