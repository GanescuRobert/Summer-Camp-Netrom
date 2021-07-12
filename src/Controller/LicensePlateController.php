<?php

namespace App\Controller;

use App\Entity\LicensePlate;
use App\Form\LicensePlateType;
use App\Repository\LicensePlateRepository;
use App\Service\ActivitiesService;
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
    public function new(Request $request, ActivitiesService $activity, MailerService $mailer, LicensePlateRepository $licensePlateRepository): Response
    {
        $licensePlate = new LicensePlate();
        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $licensePlate = $licensePlateRepository->findOneBy(['license_plate' => $licensePlate->getLicensePlate()]);
            $licensePlate->setUser($this->getUser());

            $licensePlate->setLicensePlate($this->preprocessLP($licensePlate->getLicensePlate()));
            $entityManager->persist($licensePlate);
            $entityManager->flush();

            if ($licensePlate and !$licensePlate->getUser()) {
                $blockerLP = $activity->whoBlockedMe($licensePlate->getLicensePlate());
                if ($blockerLP) {
                    $blockerEntry = $licensePlateRepository->findOneBy(['license_plate' => $blockerLP]);
                    $mailer->sendBlockerEmail($blockerEntry->getUser(), $licensePlate->getUser(), $licensePlate->getLicensePlate());
                }

                $blockeeLP = $activity->iveBlockedSomebody($licensePlate->getLicensePlate());
                if ($blockeeLP) {
                    $blockeeEntry = $licensePlateRepository->findOneBy(['license_plate' => $blockeeLP]);
                    $this->addFlash('danger', 'You blocked someone!' . ' Email sent to ' . $blockeeEntry->getUser()->getEmail());
                }
            }
            return $this->redirectToRoute('license_plate_index');
        }
        return $this->render('license_plate/new.html.twig', [
                'license_plate' => $licensePlate,
                'form' => $form->createView(),
            ]);

    }

    public function preprocessLP(string $licensePlate)
    {
        #$licensePlate = str_replace(' ', '-', $licensePlate);
        $licensePlate = preg_replace('/[^A-Za-z0-9]/', '', $licensePlate);
        $licensePlate = strtoupper($licensePlate);
        return $licensePlate;
    }

    #[Route('/{id}', name: 'license_plate_show', methods: ['GET'])]
    public function show(LicensePlate $licensePlate): Response
    {
        return $this->render('license_plate/show.html.twig', [
            'license_plate' => $licensePlate,
        ]);
    }

    #[Route('/{id}/edit', name: 'license_plate_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LicensePlate $licensePlate): Response
    {
        $form = $this->createForm(LicensePlateType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('license_plate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('license_plate/edit.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'license_plate_delete', methods: ['POST'])]
    public function delete(Request $request, LicensePlate $licensePlate): Response
    {
        if ($this->isCsrfTokenValid('delete' . $licensePlate->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($licensePlate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('license_plate_index', [], Response::HTTP_SEE_OTHER);
    }
}
