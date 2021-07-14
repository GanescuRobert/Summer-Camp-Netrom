<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\BlockeeType;
use App\Form\BlockerType;
use App\Form\ChangePasswordType;
use App\Form\UserType;
use App\Repository\LicensePlateRepository;
use App\Service\LicenseplatesService;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{

    #[Route('/', name: 'my_account')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/changepassword', name: 'change_pwd')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $user = $this->getUser();
        if ($user == null)
            return $this->redirectToRoute("app_login");


        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('new_password')->getData();

            $entityManager = $this->getDoctrine()->getManager();

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

            $entityManager->persist($this->getUser());
            $entityManager->flush();

            $this->addFlash('success', 'Password was changed successfully!');
            return $this->redirectToRoute('my_account');
        }

        return $this->render(
            'user/changepwd.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/imblocker', name: 'imblocker')]
    public function imblocker(Request $request, LicensePlateRepository $licensePlateRepository, MailerService $mailer, LicenseplatesService $licenseplatesService): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockerType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setBlockee($licenseplatesService->processLicenseplate($activity->getBlockee()));
            $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlockee()]);
            if ($blockeeEntry) {
                $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlocker()]);
                $mailer->sendBlockeeEmail($blockerEntry->getUser(), $blockeeEntry->getUser(), $blockerEntry->getLicensePlate());
                $this->addFlash('danger', 'You blocked someone!' . ' Email sent to ' . $blockeeEntry->getUser()->getEmail());
            } else {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlockee());
                $entityManager->persist($licensePlate);
                $entityManager->flush();
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('my_account');
        }

        return $this->render('activity/imblocker.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/imblockee', name: 'imblockee')]
    public function imblockee(Request $request, LicensePlateRepository $licensePlateRepository, MailerService $mailer, LicenseplatesService $licenseplatesService): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockeeType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setBlocker($licenseplatesService->processLicenseplate($activity->getBlocker()));
            $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlocker()]);
            if ($blockerEntry) {
                $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $activity->getBlockee()]);
                $mailer->sendBlockerEmail($blockerEntry->getUser(), $blockeeEntry->getUser(), $blockeeEntry->getLicensePlate());
                $this->addFlash('warning', 'Email sent to ' . $blockerEntry->getUser()->getEmail());
            } else {
                $licensePlate = new LicensePlate();
                $entityManager = $this->getDoctrine()->getManager();
                $licensePlate->setLicensePlate($activity->getBlocker());
                $entityManager->persist($licensePlate);
                $entityManager->flush();
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('my_account');
        }

        return $this->render('activity/imblockee.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }


}
