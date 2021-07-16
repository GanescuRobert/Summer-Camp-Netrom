<?php


namespace App\Service;


use App\Entity\LicensePlate;
use App\Entity\User;
use App\Repository\LicensePlateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;

class LicenseplatesService
{
    protected LicensePlateRepository $licenseplateRepository;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $licenseplateRepository = null;
        $this->$licenseplateRepository = $em->getRepository(Licenseplate::class);
    }

    public function processLicenseplate(string $licensePlate): string
    {
        $licensePlate = preg_replace('/[^A-Za-z0-9]/', '', $licensePlate);
        $licensePlate = strtoupper($licensePlate);
        return $licensePlate;
    }

    /**
     * @param User $user
     * @return array|null
     */
    public function getAllLicensePlates(User $user): ?array
    {
        $indexLicensePlate = $this->licenseplateRepository->findBy(['user' => $user]);

        foreach ($indexLicensePlate as &$licensePlates) {
            $licensePlates = $licensePlates->getLicensePlate();
        }

        return $indexLicensePlate;
    }
}