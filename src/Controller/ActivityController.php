<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Service\ActivitiesService;
use App\Service\LicenseplatesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity')]
class ActivityController extends AbstractController
{
    #[Route('/', name: 'activity')]
    public function activity(Request $request, ActivitiesService $activityService, LicenseplatesService $licenseplateService): Response
    {
        $user = $this->getUser();
        return $this->render('activity/index.html.twig', [
            'user' => $user,
            'activity_blockers' => $activityService->getMyLPSasBlocker($user, $licenseplateService),
            'activity_blockees' => $activityService->getMyLPSasBlockee($user, $licenseplateService),
        ]);
    }

    #[Route('/sendtime/{blocker}', name: 'send_time')]
    public function delete_activity(Request $request, Activity $activity): Response
    {
        return $this->redirectToRoute('activity/index');
    }

    #[Route('/delete/{blocker}', name: 'delete_activity')]
    public function delete(Request $request, Activity $activity): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activity->getBlocker(), $request->request->get('_token'))) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($activity);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Activity solved!'
            );
        }

        return $this->redirectToRoute('activity/index');
    }

}
