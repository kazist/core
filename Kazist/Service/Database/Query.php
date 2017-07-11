<?php

namespace Kazist\Service\Database;

defined('KAZIST') or exit('Not Kazist Framework');

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Cache\QueryCacheProfile;

/*
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */
use Kazist\KazistFactory;

/**
 * Description of Query
 *
 * @author sbc
 */
class Query extends QueryBuilder {

    //put your code here
    private $db = '';
    private $container = '';
    private $prefix = '';
    private $query_str = '';

    public function __construct() {
        global $sc;

        $this->container = $sc;
        $this->db = $this->getDatabase();
        $this->prefix = $this->container->get('doctrine')->prefix;

        parent::__construct($this->db);
    }

    public function replacePrefix($string) {

        $factory = new KazistFactory;

        $query = str_replace('#__', $this->prefix, $string);

        $factory->loggingMessage($query);

        return $query;
    }

    public function removePrefix($string) {

        $tmp_string = str_replace($this->prefix, '', $string);

        return $tmp_string;
    }

    public function setQuery($query_str) {
        $this->query_str = $this->replacePrefix($query_str);
    }

    public function getQuery() {

        $query_str = $this->replacePrefix($this->getSQL());

        return $query_str;
    }

    public function getStatement() {

        $query_str = ($this->query_str <> '') ? $this->query_str : $this->getQuery();

        $cache_profile = $this->getQueryCacheProfile($query_str);
        $tmp_parameters = $this->getParameters();
        $parameters = (!empty($tmp_parameters)) ? $tmp_parameters : array();

        $stmt = $this->db->executeQuery($query_str, $parameters, array(), $cache_profile);

        return $stmt;
    }

    public function executeQuery($query_str) {

        $query_str = $this->replacePrefix($query_str);

        $stmt = $this->db->prepare($query_str);

        $parameters = $this->getParameters();

        $stmt->execute($parameters);

        $this->closeDatabase();

        return $stmt;
    }

    public function lastInsertId() {

        $id = $this->db->lastInsertId();

        $this->closeDatabase();

        return $id;
    }

    public function clear() {
        $this->resetQueryParts();
        $this->setParameters(array());
    }

    public function tableColumnsArray($table_name) {

        $tmp_array = array();

        $fields = $this->tableColumns($table_name);

        foreach ($fields as $key => $field) {
            $tmp_array[] = $key;
        }

        return $tmp_array;
    }

    public function tableColumns($table_name) {

        $sm = $this->db->getSchemaManager();

        $columns = $sm->listTableColumns($this->replacePrefix($table_name));

        $this->closeDatabase();

        return $columns;
    }

    public function executeUpdate($query_str = '') {

        $query_str = ($query_str <> '') ? $query_str : $this->getQuery();

        $parameters = $this->getParameters();

        $this->db->executeUpdate($query_str, $parameters);

        $id = $this->db->lastInsertId();

        $this->closeDatabase();

        return $id;
    }

    public function execute() {

        $query_str = $this->getQuery();

        $stmt = $this->db->prepare($query_str);

        $parameters = $this->getParameters();

        $stmt->execute($parameters);

        $this->closeDatabase();
    }

    public function loadResult() {

        $column = $this->fetchColumn();

        $this->closeDatabase();

        return $column;
    }

    public function fetchColumn() {
        $stmt = $this->getStatement();

        $record = $stmt->fetchColumn();

        $this->closeDatabase();

        return $record;
    }

    public function fetch() {
        try {

            $stmt = $this->getStatement();

            $record = $stmt->fetch();

            $stmt->closeCursor();

            return $record;
        } catch (Exception $ex) {
            throw new $ex;
        }
    }

    public function loadObjectList() {
        try {

            $records = $this->fetchAll();

            $new_records = json_decode(json_encode($records));

            return $new_records;
        } catch (\Exception $ex) {
            $this->loggingException($ex);
            throw $ex;
        }
    }

    public function fetchAll() {

        try {

            $stmt = $this->getStatement();

            $records = $stmt->fetchAll();

            $stmt->closeCursor();

            return $records;
        } catch (\Exception $ex) {
            $this->loggingException($ex);
            throw $ex;
        }

        return false;
    }

    public function loadObject() {
        try {

            $record = $this->fetch();

            $new_record = json_decode(json_encode($record));

            return $new_record;
        } catch (\Exception $ex) {
            $this->loggingException($ex);
            throw $ex;
        }
    }

    public function fetchObject() {
        try {

            $record = $this->fetch();

            $new_record = json_decode(json_encode($record));

            return $new_record;
        } catch (\Exception $ex) {
            $this->loggingException($ex);
            throw $ex;
        }
    }

    public function loggingException($ex) {
        $factory = new KazistFactory();
        $factory->loggingException($ex);
    }

    public function getQueryCacheProfile($query_str = '') {

        $query_str_key = md5($query_str);
        $cache = $this->container->get('doctrine.cache');
        $cache_lifetime = $this->container->getParameter('database.cache.lifetime');

        return new QueryCacheProfile($cache_lifetime, $query_str_key, $cache);
    }

    public function getTableAlias($table_name = '') {

        $table_alias_arr = array();
        $table_name_arr = explode('_', str_replace('#__', '', $table_name));

        if (!empty($table_name_arr)) {
            foreach ($table_name_arr as $key => $term) {
                $table_alias_arr[] = substr($term, 0, 1);
            }
        }

        $this->table_alias = implode('', $table_alias_arr);

        return $this->table_alias;
    }

    public function getDatabase() {

        $entityManager = $this->container->get('doctrine')->getEntityManager();

        $db = $entityManager->getConnection();

        return $db;
    }

    public function closeDatabase() {

        // $this->db->close();
    }

}
