<?php


namespace App\Service;


use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class MailerService
{
    private MailerInterface $mailer;

    /**
     * MailerService constructor.
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param User $user
     * @param String $password
     */
    #[Route('/email', name: 'email')]
    public function sendEmail(User $user, string $password)
    {
        $email = (new TemplatedEmail())
            ->from('contact@whoblockedme.com')
            ->to($user->getEmail())
            ->subject('Account password for Who Blocked Me?')
            ->htmlTemplate('mailer/email_registration.html.twig')
            ->context([
                'username' => $user->getId(),
                'password' => $password,
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
        }
    }
    /**
     * @param User $blocker
     * @param User $blockee
     * @param string $license_plate
     * @throws TransportExceptionInterface
     */
    public function sendBlockerEmail(User $blocker, User $blockee, string $license_plate)
    {
        $email = (new TemplatedEmail())
            ->from('contact@whoblockedme.com')
            ->to($blocker->getUserIdentifier())
            ->subject('You blocked somebody!')
            ->htmlTemplate('mailer/blockerEmail.html.twig')

            ->context([
                'blockee' => $blockee->getUserIdentifier(),
                'blockee_license_plate' => $license_plate,
            ]);

        $this->mailer->send($email);
    }

    /**
     * @param User $blocker
     * @param User $blockee
     * @param string $license_plate
     * @throws TransportExceptionInterface
     */
    public function sendBlockeeEmail(User $blocker, User $blockee, string $license_plate)
    {
        $email = (new TemplatedEmail())
            ->from('contact@whoblockedme.com')
            ->to($blockee->getUserIdentifier())
            ->subject('You are blocked by somebody!')
            ->htmlTemplate('mailer/blockeeEmail.html.twig')

            ->context([
                'blocker' => $blocker->getUserIdentifier(),
                'blocker_license_plate' => $license_plate,
            ]);

        $this->mailer->send($email);
    }
}