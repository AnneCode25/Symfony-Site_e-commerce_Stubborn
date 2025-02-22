<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user): void
    {
        // La génération de la signature reste la même
        $signedUrl = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        )->getSignedUrl();

        $email = (new TemplatedEmail())
            ->from('stubborn@blabla.com')
            ->to($user->getEmail())
            ->subject('Confirmez votre adresse email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'user' => $user,
                'signedUrl' => $signedUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * La nouvelle méthode utilise verifyUserEmail au lieu de validateEmailConfirmation
     */
    public function handleEmailConfirmation(string $requestUri, User $user): void
    {
        try {
            // verifyUserEmail est la nouvelle méthode recommandée
            $this->verifyEmailHelper->verifyUserEmail(
                $user,              // L'utilisateur complet est maintenant passé
                $requestUri,        // L'URI de la requête
                $user->getId(),     // L'identifiant de l'utilisateur
                $user->getEmail()   // L'email de l'utilisateur
            );

            $user->setIsVerified(true);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

        } catch (VerifyEmailExceptionInterface $e) {
            throw $e;
        }
    }
}