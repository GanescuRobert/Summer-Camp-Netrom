<?php


namespace App\Service;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Validator\Constraints as Assert;

class ActivitiesService
{
    /**
     * @var ActivityRepository
     */
    protected $activityRepo;
    private EntityManagerInterface $entityManager;

    /**
     * @SecurityAssert\UserPassword(
     *     message = "Wrong value for your current password"
     * )
     */
    protected $oldPassword;
    /**
     * @Assert\Length(
     *     min = 6,
     *     minMessage = "Password should by at least 6 chars long"
     * )
     */
    protected $newPassword;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->activityRepo = $em->getRepository(Activity::class);
    }

    /**
     * @param string $licensePlate
     * @return string|null
     * @throws NonUniqueResultException
     */
    public function iveBlockedSomebody(string $licensePlate): ?string
    {
        $blocker = $this->activityRepo->findByBlocker($licensePlate);

        if ($blocker instanceof Activity) {
            return $blocker->getBlockee();
        }
        return '';
    }

    /**
     * @param string $licensePlate
     * @return string|null
     * @throws NonUniqueResultException
     */
    public function whoBlockedMe(string $licensePlate): ?string
    {
        $blocker = $this->activityRepo->findByBlockee($licensePlate);

        if ($blocker instanceof Activity) {
            return $blocker->getBlocker();
        }
        return '';
    }

}