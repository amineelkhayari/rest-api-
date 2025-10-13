<?php
namespace Core;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;

class DoctrineOrmFactory
{
    public static function createEntityManager(): EntityManager
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/Entities'], // your entities directory
            isDevMode: true
        );

        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/../../data/database.sqlite',
        ];

        $connection = DriverManager::getConnection($connectionParams, $config);

        return new EntityManager($connection, $config);
    }
}
