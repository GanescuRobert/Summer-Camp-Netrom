<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Entity\User;
use App\Form\BlockeeType;
use App\Form\BlockerType;
use App\Form\ChangePasswordType;
use App\Repository\LicensePlateRepository;
use App\Service\ActivitiesService;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/homepage', name: 'homepage')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    #[Route('/imblocker', name: 'imblocker')]
    public function imblocker(Request $request, LicensePlateRepository $licensePlateRepository, MailerService $mailer): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockerType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setBlockee($this->preprocessLP($activity->getBlockee()));
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

            return $this->redirectToRoute('homepage');
        }

        return $this->render('activity/imblocker.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    #[Route('/imblockee', name: 'imblockee')]
    public function imblockee(Request $request, LicensePlateRepository $licensePlateRepository, MailerService $mailer): Response
    {
        $activity = new Activity();
        $form = $this->createForm(BlockeeType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setBlocker($this->preprocessLP($activity->getBlocker()));
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

            return $this->redirectToRoute('homepage');
        }

        return $this->render('activity/imblockee.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/changepassword', name: 'change_pwd')]
    public function changePassword(Request $request, ActivitiesService $activitiesService)
    {
        $user = new User();
        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            // 3) save the activity!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirect($this->generateUrl('homepage'));
        }

        return $this->render('homepage/changepwd.html.twig', [

            'form' => $form->createView(),
        ]);
    }
    public function preprocessLP(string $licensePlate){
        $licensePlate = str_replace(' ', '-', $licensePlate);
        $licensePlate = preg_replace('/[^A-Za-z0-9]/', '', $licensePlate);
        $licensePlate = strtoupper($licensePlate);
        return $licensePlate;
    }
}
