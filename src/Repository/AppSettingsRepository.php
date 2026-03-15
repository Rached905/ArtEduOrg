<?php

namespace App\Repository;

use App\Entity\AppSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppSettings>
 */
class AppSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppSettings::class);
    }

    /**
     * Returns the single app settings row (singleton). Creates default one if none exists.
     */
    public function getSettings(): AppSettings
    {
        $settings = $this->findOneBy([]);
        if ($settings === null) {
            $settings = new AppSettings();
            $settings->setSiteName('ArtEduOrg');
            $settings->setItemsPerPage(12);
        }
        return $settings;
    }
}
