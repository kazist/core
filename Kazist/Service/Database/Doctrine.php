<?php

namespace Kazist\Service\Database;

defined('KAZIST') or exit('Not Kazist Framework');
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Doctrine\Common\ClassLoader,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager,
    Doctrine\DBAL\Types\Type,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\DBAL\Logging\EchoSqlLogger;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;

class Doctrine {

    public $container = '';
    public $refresh = false;
    public $load_prefix = true;
    public $prefix = 'kazist_';
    public $entity_path = '';
    public $entities_path = array();

    public function __construct() {

        global $sc;

        $this->container = $sc;

        $this->prefix = ($sc->hasParameter('database.prefix')) ? $this->container->getParameter('database.prefix') : 'kazist';

        if (!$this->container->hasParameter('database.cache')) {
            $this->container->setParameter('database.cache', false);
        }

        if (!$this->container->hasParameter('database.cache.lifetime')) {
            $this->container->setParameter('database.cache.lifetime', 7200);
        }
    }

    public function getEntityManager() {

        // Set up caches
        $config = new Configuration;
        $cache = new ArrayCache;
        $evm = new EventManager;

        $this->container->set('doctrine.cache', $cache);

        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);

        $entities_path = $this->getEntitiesPath();

        if ($this->container->has('entity.manager') && $this->entity_path == '' && !$this->refresh) {
            return $this->container->get('entity.manager');
        }

        // Doctrine Event List
        if ($this->load_prefix) {
            $tablePrefix = new DoctrinePrefixListener($this->prefix);
            $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, $tablePrefix);
        }

        // Metadata Driver
        if ($this->entity_path <> '') {
            AnnotationRegistry::registerFile(JPATH_ROOT . '/vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
            $driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
                    new \Doctrine\Common\Annotations\AnnotationReader(), array($this->entity_path)
            );
        } else {
            AnnotationRegistry::registerLoader('class_exists');
            $driverImpl = new AnnotationDriver(new AnnotationReader(), $entities_path);
        }

        // registering noop annotation autoloader - allow all annotations by default
        $config->setMetadataDriverImpl($driverImpl);

        // Proxy configuration
        $config->setProxyDir(JPATH_ROOT . '/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setAutoGenerateProxyClasses(JPATH_ROOT);

        // Database connection information
        $connectionOptions = $this->getDbConfig();

        // Create EntityManager
        $entityManager = EntityManager::create($connectionOptions, $config, $evm);

        if ($this->entity_path <> '' && is_dir($this->entity_path)) {

            $schemaManager = $entityManager->getConnection()->getSchemaManager();
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
            $classes = $entityManager->getMetadataFactory()->getAllMetadata();

            foreach ($classes as $class) {

                $table_name = $class->table['name'];

                if ($schemaManager->tablesExist(array($table_name)) == true) {
                    $sql = $schemaTool->getUpdateSchemaSql(array($class));
                } else {
                    $sql = $schemaTool->getCreateSchemaSql(array($class));
                }

                foreach ($sql as $statement) {
                    if (strpos($statement, "DROP TABLE") === false) {
                        $entityManager->getConnection()->exec($statement);
                    }
                }
            }
        }

        // if ($this->container->getParameter('database.cache')) {
        //  $entityManager->useResultCache(true);
        //  }

        $this->container->set('entity.manager', $entityManager);

        return $entityManager;
    }

    function deleteArrayCache() {
        $cacheDriver = new ArrayCache();
        $cacheDriver->deleteAll();
    }

    function getDbConfig() {

        //An example configuration

        return array(
            'driver' => $this->container->getParameter('database.driver'),
            'user' => $this->container->getParameter('database.user'),
            'password' => $this->container->getParameter('database.password'),
            'dbname' => $this->container->getParameter('database.name'),
            'host' => $this->container->getParameter('database.host'),
            'charset' => 'utf8',
            'driverOptions' => array(
                1002 => 'SET NAMES utf8'
            )
        );
    }

    function bootstrapDoctrine() {
        require_once ($this->_libDir . DS . 'Doctrine/ORM/Tools/Setup.php');
        Doctrine\ORM\Tools\Setup::registerAutoloadDirectory('/full/path/to/lib'); //So that Doctrine is in /full/path/to/lib/Doctrine   
    }

    function getEntityFolders() {
        //An example configuration of two entity folders
        return array(
            '/full/path/to/App/Module1/Entities/yml' => '\\App\\Module1\\Entities',
            '/full/path/to/App/Module2/Entities/yml' => '\\App\\Module2\\Entities'
        );
    }

    function setupDoctrine() {
        $config = Doctrine\ORM\Tools\Setup::createConfiguration();
        $driver = new \Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver(getEntityFolders());
        $driver->setGlobalBasename('schema');
        $config->setMetadataDriverImpl($driver);

        $entityManager = \Doctrine\ORM\EntityManager::create($dbConfig, $config);
        return $entityManager;
    }

    function getEntitiesMetaData($em) {
        $cmf = new Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($em);  // we must set the EntityManager

        $driver = $em->getConfiguration()->getMetadataDriverImpl();

        $classes = $driver->getAllClassNames();
        $metadata = array();
        foreach ($classes as $class) {
            //any unsupported table/schema could be handled here to exclude some classes
            if (true) {
                $metadata[] = $cmf->getMetadataFor($class);
            }
        }
        return $metadata;
    }

    function generateEntities($rootDir, $metadata) {
        $generator = new Doctrine\ORM\Tools\EntityGenerator();
        $generator->setUpdateEntityIfExists(true);    // only update if class already exists
        //$generator->setRegenerateEntityIfExists(true);  // this will overwrite the existing classes
        $generator->setGenerateStubMethods(true);
        $generator->setGenerateAnnotations(true);
        $generator->generate($metadata, $rootDir);
    }

    function generateDatabase() {
        $schema = new Doctrine\ORM\Tools\SchemaTool($em);
        $schema->createSchema($metadata);
    }

    public function setEntitiesPath($paths) {

        $this->entities_path = $paths;
    }

    public function getEntitiesPath() {

        return $this->entities_path;
    }

}
