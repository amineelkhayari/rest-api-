<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap.php';

use Core\DoctrineOrmFactory;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

$entityManager = DoctrineOrmFactory::createEntityManager();
return ConsoleRunner::createHelperSet($entityManager);
