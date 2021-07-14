<?php


namespace App\Service;


class LicenseplatesService
{
    public function processLicenseplate(string $licensePlate): string
    {
        $licensePlate = preg_replace('/[^A-Za-z0-9]/', '', $licensePlate);
        $licensePlate = strtoupper($licensePlate);
        return $licensePlate;
    }
}