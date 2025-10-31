<?php
namespace Core;

use Core\Entities\Amine;  // ✅ this is REQUIRED — import your entity properly
use Doctrine\ORM\EntityManager;

class Database
{
    private EntityManager $entityManager;

    public function __construct()
    {
        $this->entityManager = DoctrineOrmFactory::createEntityManager();
    }

    /**
     * (Optional dummy connection info — you can remove this if unused)
     */
    public function getConnection(): array
    {
        return ['test', 'user'];
    }

    /**
     * Return the Doctrine EntityManager instance
     */
    public function getConector(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Fetch data from the database.
     * Defaults to the Amine entity if no class is provided.
     */
    public function getData(string $entityClass = Amine::class): array
    {
        return $this
            ->entityManager
            ->getRepository($entityClass)
            ->findAll();
    }

    /**
     * ✅ Save or update an entity
     */
    public function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
