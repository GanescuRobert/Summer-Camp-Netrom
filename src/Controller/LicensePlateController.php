<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlate;
use App\Form\LicensePlateType;
use App\Repository\LicensePlateRepository;
use App\Service\ActivitiesService;
use App\Service\LicenseplatesService;
use App\Service\MailerService;
use Doctrine\ORM\NonUniqueResultException as NonUniqueResultExceptionAlias;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/licenseplate')]
class LicensePlateController extends AbstractController
{
    #[Route('/', name: 'license_plate_index', methods: ['GET'])]
    public function index(LicensePlateRepository $licensePlateRepository): Response
    {
        return $this->render('license_plate/index.html.twig', [
            'license_plates' => $licensePlateRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    /**
     * @throws NonUniqueResultExceptionAlias
     */
    #[Route('/new', name: 'license_plate_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ActivitiesService $activity, MailerService $mailer, LicensePlateRepository $licensePlateRepository, LicenseplatesService $licenseplatesService): Response
    {
        $user = $this->getUser();
        $licensePlate = new LicensePlate();
        $entityManager = $this->getDoctrine()->getManager();

        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $licensePlate->setLicensePlate($licenseplatesService->processLicenseplate($licensePlate->getLicensePlate()));
            $entrylicensePlate = $licensePlateRepository->findOneBy(['license_plate' => $licensePlate->getLicensePlate()]);
            $licensePlate->setUser($user);

            $entityManager->persist($licensePlate);
            $entityManager->flush();

            if ($entrylicensePlate and !$entrylicensePlate->getUser()) {
                $entrylicensePlate->setUser($user);

                $blockerLP = $activity->whoBlockedMe($licensePlate->getLicensePlate());
                $blockeeLP = $activity->iveBlockedSomebody($licensePlate->getLicensePlate());

                if ($blockerLP) {
                    $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $blockerLP]);
                    $activity = $entityManager->getRepository(Activity::class)->findOneBy(['blocker' => $blockerLP]);

                    $mailer->sendBlockerEmail($blockerEntry->getUser(), $entrylicensePlate->getUser(), $licensePlate->getLicensePlate());
                    $activity->setStatus(1);
                    $this->addFlash('warning', 'Your car has been blocked by ' . $activity->getBlocker());
                }
                if ($blockeeLP) {
                    $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $blockeeLP]);
                    $activity = $entityManager->getRepository(Activity::class)->findOneBy(['blockee' => $blockeeLP]);

                    $mailer->sendBlockerEmail($blockeeEntry->getUser(), $entrylicensePlate->getUser(), $blockeeLP);
                    $activity->setStatus(1);
                    $this->addFlash('danger', 'You blocked someone!' . ' Email sent to ' . $blockeeEntry->getUser()->getEmail());
                }
            } else {
                $this->addFlash(
                    'success',
                    'The car ' . $licensePlate->getLicensePlate() . ' has been added to your account!'
                );
            }
            return $this->redirectToRoute('license_plate_index');
        }
        return $this->render('license_plate/new.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);

    }

    #[Route('/{id}', name: 'license_plate_show', methods: ['GET'])]
    public function show(LicensePlate $licensePlate): Response
    {
        return $this->render('license_plate/show.html.twig', [
            'license_plate' => $licensePlate,
        ]);
    }

    #[Route('/{id}/edit', name: 'license_plate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LicensePlate $licenseplate, LicenseplatesService $licenseplateService, LicensePlateRepository $licensePlateRepository, ActivitiesService $activityService): Response
    {
        $newlicenseplate = new LicensePlate();

        $form = $this->createForm(LicensePlateType::class, $newlicenseplate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newlicenseplate->setLicensePlate($licenseplateService->processLicenseplate($newlicenseplate->getLicensePlate()));

            if ($licenseplate->getLicensePlate() == $newlicenseplate) {
                $this->addFlash(
                    'danger',
                    "Report active."
                );
                return $this->redirectToRoute('license_plate_index');
            }

            $blocker = $activityService->iveBlockedSomebody($licenseplate);
            $blockee = $activityService->whoBlockedMe($licenseplate);
            if ($blocker || $blockee) {
                $this->addFlash(
                    'danger',
                    "Report active."
                );

                return $this->redirectToRoute('license_plate_index');
            }
            $this->addFlash(
                'success',
                "Licenseplate " . $licenseplate->getLicensePlate() . " has been changed to " . $newlicenseplate
            );
            $licenseplate->setLicensePlate($newlicenseplate);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('license_plate_index');
        }

        return $this->render('license_plate/edit.html.twig', [
            'license_plate' => $licenseplate,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws NonUniqueResultExceptionAlias
     */
    #[Route('/{id}/delete', name: 'license_plate_delete', methods: ['POST'])]
    public function delete(Request $request, LicensePlate $licensePlate, ActivitiesService $activityService): Response
    {
        $blocker = $activityService->iveBlockedSomebody($licensePlate->getLicensePlate());
        $blockee = $activityService->whoBlockedMe($licensePlate->getLicensePlate());

        if ($blocker || $blockee) {
            $this->addFlash(
                'danger',
                "Report active."
            );
            return $this->redirectToRoute('license_plate_index');
        }

        if ($this->isCsrfTokenValid('delete' . $licensePlate->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($licensePlate);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'License plate ' . $licensePlate->getLicensePlate() . ' was successfully deleted'
            );
        }
        return $this->redirectToRoute('license_plate_index');
    }
}