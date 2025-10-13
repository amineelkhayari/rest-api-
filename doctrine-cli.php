<?php
// require_once __DIR__ . '/vendor/autoload.php';
// require_once __DIR__ . '/bootstrap.php';

// use Core\DoctrineOrmFactory;
// use Doctrine\ORM\Tools\Console\ConsoleRunner;
// use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

// $entityManager = DoctrineOrmFactory::createEntityManager();

// ConsoleRunner::run(
//     new SingleManagerProvider($entityManager)
// );

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap.php';

use Core\DoctrineOrmFactory;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Tools\Console\ConsoleRunner as MigrationsConsoleRunner;
use Symfony\Component\Console\Application;

// 1️⃣ Create the EntityManager
$entityManager = DoctrineOrmFactory::createEntityManager();

// 2️⃣ Load migrations configuration
$configFile = __DIR__ . '/migrations.php';
$migrationsConfig = new PhpFile($configFile);

// 3️⃣ Create dependency factory (✅ correct type!)
$dependencyFactory = DependencyFactory::fromEntityManager(
    $migrationsConfig,
    new ExistingEntityManager($entityManager)
);

// 4️⃣ Create the CLI app
$cli = new Application('Doctrine CLI');

// 5️⃣ Add ORM commands
ConsoleRunner::addCommands($cli, new SingleManagerProvider($entityManager));

// 6️⃣ Add Migration commands
MigrationsConsoleRunner::addCommands($cli, $dependencyFactory);

// 7️⃣ Run
$cli->run();
