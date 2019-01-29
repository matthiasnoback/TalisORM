<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\DBAL\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use TalisOrm\AggregateRepositoryTest\Order;
use TalisOrm\DoctrineMigrations\DoctrineMigrationsSchemaProvider;
use TalisOrm\Schema\AggregateSchemaProvider;

$projectRootDir = dirname(dirname(dirname(__DIR__)));
require $projectRootDir . '/vendor/autoload.php';

$migrationsDirectory = $projectRootDir . '/build/migrations';
if (!@mkdir($migrationsDirectory, 0777, true) && !is_dir($migrationsDirectory)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', $migrationsDirectory));
}

$configuration = new Configuration(
    DriverManager::getConnection([
        'url' => 'sqlite:///' . $migrationsDirectory . '/db.sqlite'
    ])
);
$configuration->setMigrationsDirectory($migrationsDirectory);
$configuration->setMigrationsNamespace('Migrations');

$helperSet = new HelperSet([
    'question' => new QuestionHelper(),
    'configuration' => new ConfigurationHelper(
        null,
        $configuration
    )
]);

$migrationsSchemaProvider = new DoctrineMigrationsSchemaProvider(new AggregateSchemaProvider([
    Order::class
]));

$cli = ConsoleRunner::createApplication($helperSet, [
    new DiffCommand($migrationsSchemaProvider)
]);
$cli->run();
