<?php

namespace Dtc\QueueBundle\Tests\ORM;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use DoctrineExtensions\Query\Mysql\Day;
use DoctrineExtensions\Query\Mysql\Hour;
use DoctrineExtensions\Query\Mysql\Minute;
use DoctrineExtensions\Query\Mysql\Month;
use DoctrineExtensions\Query\Mysql\Year;
use Dtc\QueueBundle\ORM\JobManager;
use Dtc\QueueBundle\ORM\JobTimingManager;
use Dtc\QueueBundle\ORM\RunManager;
use Dtc\QueueBundle\Tests\Doctrine\DoctrineJobManagerTest;

/**
 * @author David
 *
 * This test requires local mongodb running
 */
class JobManagerTest extends DoctrineJobManagerTest
{
    public static function createObjectManager()
    {
        if (!is_dir('/tmp/dtcqueuetest/generate/proxies')) {
            mkdir('/tmp/dtcqueuetest/generate/proxies', 0777, true);
        }

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/../..'], true, null, null, false);

        AnnotationRegistry::registerFile(__DIR__.'/../../vendor/mmucklo/grid-bundle/Annotation/Grid.php');
        AnnotationRegistry::registerFile(__DIR__.'/../../vendor/mmucklo/grid-bundle/Annotation/Sort.php');
        AnnotationRegistry::registerFile(__DIR__.'/../../vendor/mmucklo/grid-bundle/Annotation/ShowAction.php');
        AnnotationRegistry::registerFile(__DIR__.'/../../vendor/mmucklo/grid-bundle/Annotation/DeleteAction.php');
        AnnotationRegistry::registerFile(__DIR__.'/../../vendor/mmucklo/grid-bundle/Annotation/Column.php');
        AnnotationRegistry::registerFile(__DIR__.'/../../vendor/mmucklo/grid-bundle/Annotation/Action.php');

        $config->addCustomNumericFunction('year', Year::class);
        $config->addCustomNumericFunction('month', Month::class);
        $config->addCustomNumericFunction('day', Day::class);
        $config->addCustomNumericFunction('hour', Hour::class);
        $config->addCustomNumericFunction('minute', Minute::class);
        $host = getenv('MYSQL_HOST');
        $user = getenv('MYSQL_USER');
        $port = getenv('MYSQL_PORT') ?: 3306;
        $password = getenv('MYSQL_PASSWORD');
        $db = getenv('MYSQL_DATABASE');
        $params = ['host' => $host,
            'port' => $port,
            'user' => $user,
            'driver' => 'mysqli',
            'password' => $password,
            'dbname' => $db, ];

        self::$objectManager = EntityManager::create($params, $config);
    }

    public static function setUpBeforeClass(): void
    {
        self::createObjectManager();
        $entityName = 'Dtc\QueueBundle\Entity\Job';
        $archiveEntityName = 'Dtc\QueueBundle\Entity\JobArchive';
        $runClass = 'Dtc\QueueBundle\Entity\Run';
        $runArchiveClass = 'Dtc\QueueBundle\Entity\RunArchive';
        $jobTimingClass = 'Dtc\QueueBundle\Entity\JobTiming';

        /** @var EntityManager $objectManager */
        $objectManager = self::$objectManager;
        $tool = new SchemaTool($objectManager);
        $metadataEntity = [$objectManager->getClassMetadata($entityName)];
        $tool->dropSchema($metadataEntity);
        $tool->createSchema($metadataEntity);

        $metadataEntityArchive = [$objectManager->getClassMetadata($archiveEntityName)];
        $tool->dropSchema($metadataEntityArchive);
        $tool->createSchema($metadataEntityArchive);

        $metadataEntityRun = [$objectManager->getClassMetadata($runClass)];
        $tool->dropSchema($metadataEntityRun);
        $tool->createSchema($metadataEntityRun);

        $metadataEntityRunArchive = [$objectManager->getClassMetadata($runArchiveClass)];
        $tool->dropSchema($metadataEntityRunArchive);
        $tool->createSchema($metadataEntityRunArchive);

        $metadataJobTiming = [$objectManager->getClassMetadata($jobTimingClass)];
        $tool->dropSchema($metadataJobTiming);
        $tool->createSchema($metadataJobTiming);

        self::$objectName = $entityName;
        self::$archiveObjectName = $archiveEntityName;
        self::$runClass = $runClass;
        self::$runArchiveClass = $runArchiveClass;
        self::$jobTimingClass = $jobTimingClass;
        self::$jobManagerClass = JobManager::class;
        self::$runManagerClass = RunManager::class;
        self::$jobTimingManagerClass = JobTimingManager::class;
        parent::setUpBeforeClass();
    }

    protected function runCountQuery($class)
    {
        /** @var JobManager $jobManager */
        $jobManager = self::$jobManager;

        /** @var EntityManager $entityManager */
        $entityManager = $jobManager->getObjectManager();

        return $entityManager->createQueryBuilder()->select('count(j.id)')->from($class, 'j')->getQuery()->getSingleScalarResult();
    }
}
