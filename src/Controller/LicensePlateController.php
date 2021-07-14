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

            if ($entrylicensePlate and !$entrylicensePlate->getUser()) {
                $entrylicensePlate->setUser($user);

                $blockerLP = $activity->whoBlockedMe($licensePlate->getLicensePlate());
                if ($blockerLP) {
                    $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $blockerLP]);
                    $activity = $entityManager->getRepository(Activity::class)->findOneBy(['blocker' => $blockerLP]);

                    $mailer->sendBlockerEmail($blockerEntry->getUser(), $entrylicensePlate->getUser(), $licensePlate->getLicensePlate());
                    $activity->setStatus(1);
                    $this->addFlash('warning', 'Your car has been blocked by ' . $activity->getBlocker());
                }

                $blockeeLP = $activity->iveBlockedSomebody($licensePlate->getLicensePlate());
                if ($blockeeLP) {
                    $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $blockeeLP]);
                    $activity = $entityManager->getRepository(Activity::class)->findOneBy(['blockee' => $blockeeLP]);

                    $mailer->sendBlockerEmail($blockeeEntry->getUser(), $entrylicensePlate->getUser(), $blockeeLP);
                    $activity->setStatus(1);
                    $this->addFlash('danger', 'You blocked someone!' . ' Email sent to ' . $blockeeEntry->getUser()->getEmail());
                }
            } else {
                $licensePlate->setUser($user);
                $entityManager->persist($licensePlate);
                $entityManager->flush();
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
    public function edit(Request $request, LicensePlate $licensePlate, LicenseplatesService $licenseplatesService): Response
    {
        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);
        $licensePlate->setLicensePlate($licenseplatesService->processLicenseplate($licensePlate->getLicensePlate()));
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                'success',
                'The car ' . $licensePlate->getLicensePlate() . ' has been updated!'
            );
            return $this->redirectToRoute('license_plate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('license_plate/edit.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'license_plate_delete', methods: ['POST'])]
    public function delete(Request $request, LicensePlate $licensePlate, ActivitiesService $activityService): Response
    {
        $oldLicensePlate = $licensePlate->getLicensePlate();

        $blocker = $activityService->iveBlockedSomebody($oldLicensePlate);
        $blockee = $activityService->whoBlockedMe($oldLicensePlate);

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
                'License plate ' . $licensePlate->getLicensePlate() . ' was successfully deleted!'
            );
        }

        return $this->redirectToRoute('license-plate/index');
    }
}