<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/* * $entity
 * Description of LeapYear
 * $entity
 * @author sbc
 */

namespace Kazist\Model;

defined('KAZIST') or exit('Not Kazist Framework');

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Query\Expr\Join;
use Kazist\Service\Database\Query;
use Kazist\Service\Media\MediaManager;
use Kazist\Service\Form\DefaultField;
use Kazist\Service\Form\Validation;
use Kazist\KazistFactory;
use Kazist\StringModification;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BaseModel extends KazistModel {

    public $table_alias = 'tx';
    public $limit = 10;
    public $offset = 0;
    public $ordering = 'DESC';
    public $query_failed_attempt = 0;

    public function getRecords($offset, $limit) {

        $document = $this->container->get('document');
        $request = $this->container->get('request');
        $query = $this->getQueryBuilder();

        $offset = $this->getOffset();
        $limit = $this->getLimit();

        $this->appendSearchQuery($query);

        $query->setFirstResult($offset);
        $query->setMaxResults($limit);

        $query->addOrderBy($this->table_alias . '.id', 'DESC');

        try {
            $records = $query->loadObjectList();
        } catch (\Exception $ex) {

            if (!$this->query_failed_attempt) {
                $this->autoInstallTable($document->extension_path);
                $this->getRecords($offset, $limit);
                $this->query_failed_attempt++;
            }

            $this->loggingException($ex);
            throw $ex;
        }

        return json_decode(json_encode($records));
    }

    public function getQueryedRecords($table_name, $table_alias, $where_arr = array(), $parmeter_arr = array(), $ordering_arr = array(), $offset = '', $limit = '') {

        $query = $this->getQueryBuilder($table_name, $table_alias, $where_arr, $parmeter_arr, $ordering_arr);

        if ($offset) {
            $query->setFirstResult($offset);
        }

        if ($limit) {
            $query->setMaxResults($limit);
        }

        $query->addOrderBy($this->table_alias . '.id', 'DESC');



        $record = $query->loadObjectList();

        return $record;
    }

    public function getRecord($id = '', $query = '') {

        $where_arr = array();
        $parameter_arr = array();
        $document = $this->container->get('document');

        $query = (is_object($query)) ? $query : $this->getQueryBuilder();

        $slug = $this->request->get('slug');
        $id = ($id) ? $id : $this->request->get('id');

        if ($id) {
            $query->andWhere($this->table_alias . '.id=:id');
            $query->setParameter('id', $id);
        } elseif ($slug) {
            $query->andWhere($this->table_alias . '.slug=:slug');
            $query->setParameter('slug', $slug);
        } else {
            $query->andWhere('1=-1');
        }

        try {
            $record = $query->loadObject();
        } catch (\Exception $ex) {

            if (!$this->query_failed_attempt) {
                $this->autoInstallTable($document->extension_path);
                $this->getRecord($id, $query);
                $this->query_failed_attempt++;
            }

            $this->loggingException($ex);
            throw $ex;
        }

        $document = $this->container->get('document');
        $document->record_id = $record->id;

        return json_decode(json_encode($record));
    }

    public function getQueryedRecord($table_name, $table_alias, $where_arr = array(), $parmeter_arr = array(), $ordering_arr = array()) {
        try {
            $query = $this->getQueryBuilder($table_name, $table_alias, $where_arr, $parmeter_arr, $ordering_arr);

            $record = $query->loadObject();

            return $record;
        } catch (Exception $ex) {
            throw new $e;
        }
    }

    public function getOffset() {

        $document = $this->container->get('document');

        $this->offset = ($document->offset) ? $document->offset : $this->offset;


        return $this->offset;
    }

    public function getLimit() {

        $document = $this->container->get('document');

        $this->limit = ($document->limit) ? $document->limit : $this->limit;

        return $this->limit;
    }

    public function getTotal($query = '') {

        $where_arr = array();
        $parameter_arr = array();

        $request = $this->container->get('request');
        $search = $request->request->get('search');

        if (!is_object($query)) {
            $query = $this->getQueryBuilder();


            $this->appendSearchQuery($query, $search);
            $this->addWhereToQuery($query, $where_arr, $parameter_arr);

            $query->setMaxResults(1);
        }

        $query->select('COUNT(*) AS total');

        $record = $query->loadObject();

        return $record->total;
    }

    public function saveHit($record) {

        $json = $this->getJson();

        $data = new \stdClass();
        $data->id = $record->id;
        $data->hit = $record->hit + 1;
        $data->hits = $record->hits + 1;

        if ($record->id) {
            $this->saveRecord('#__' . $json['table_name'], $data);
        }
    }

    public function updateStatus() {

        $form_data = $this->request->request->get('form');

        $id = $this->request->request->get('item_id');
        $item_status = $this->request->request->get('item_status');
        $item_field = $this->request->request->get('item_field');

        $data_entity = $this->getEntityObject($id);

        if ($data_entity->getId()) {

            $form_data[$item_field] = (!$item_status) ? 1 : 0;

            $this->saveEntity($data_entity, $form_data);

            return $data_entity->getId();
        }
    }

    public function delete($delete_ids = array()) {

        $entityManager = $this->container->get('doctrine')->getEntityManager();

        $form_data = $this->request->get('form');
        $request_id = $this->request->get('id');
        $request_cid = $this->request->get('cid');

        if ($form_data['id']) {
            $delete_ids[] = $form_data['id'];
        }

        if ($form_data['cid']) {
            $delete_ids = array_merge($delete_ids, $form_data['cid']);
        }

        if ($request_id) {
            $delete_ids[] = $request_id;
        }

        if ($request_cid) {
            $delete_ids = array_merge($delete_ids, $request_cid);
        }

        foreach ($delete_ids as $delete_id) {
            $data_entity = $this->getEntityObject($delete_id);

            $new_data_entity = $entityManager->merge($data_entity);
            $entityManager->remove($new_data_entity);
        }

        $entityManager->flush();
        $entityManager->getConnection()->close();
    }

    public function save($form_data = '') {


        $factory = new KazistFactory();

        $form_data = (!empty($form_data)) ? $form_data : $this->request->get('form');
        $document = $this->container->get('document');
        $extension_path = $document->extension_path;

        $json = $this->getJson();
        $data_entity = $this->getEntityObject();

        $form_data = $this->validateForm($form_data);


        try {

            $this->saveEntity($data_entity, $form_data);
        } catch (\Exception $ex) {
            print_r($ex->getMessage());
            exit;
        }

        $id = $data_entity->getId();

        $this->saveMultiple($form_data, $id);

        return $data_entity->getId();
    }

    //xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Validation
    public function validateForm($form_data, $table_name = '') {

        $slug = '';

        $query = new Query();
        $factory = new KazistFactory();
        $validation = new Validation();
        $string = new StringModification();

        $json = $this->getJson($table_name);
        $field_keys = array_keys($form_data);

        $tmp_table_name = $json['table_name'];
        $table_fields = $query->tableColumns('#__' . $tmp_table_name);


        foreach ($table_fields as $table_field) {

            $field_name = $table_field->getName();
            $field_type = (isset($json['fields'][$field_name])) ? $json['fields'][$field_name]['mysql_type'] : '';
            $html_type = (isset($json['fields'][$field_name])) ? $json['fields'][$field_name]['html_type'] : '';
            $field_length = $table_field->getLength();
            $field_not_null = $table_field->getNotnull();

            if (!in_array($field_name, $field_keys)) {
                continue;
            }

            $validation->validate($field_name, $form_data, $html_type, $field_type);

            switch ($field_type) {
                case 'date':
                    $form_data[$field_name] = ($this->validateDate($form_data[$field_name], 'Y-m-d')) ? $form_data[$field_name] : NULL;
                    $form_data[$field_name] = ( is_null($form_data[$field_name]) && $field_not_null) ? date('Y-m-d') : $form_data[$field_name];

                    break;
                case 'time':
                    $form_data[$field_name] = ($this->validateDate($form_data[$field_name], 'H:i:s')) ? $form_data[$field_name] : NULL;
                    $form_data[$field_name] = ( is_null($form_data[$field_name]) && $field_not_null) ? date('H:i:s') : $form_data[$field_name];
                    break;
                case 'datetime':
                    $form_data[$field_name] = ($this->validateDate($form_data[$field_name], 'Y-m-d H:i:s')) ? $form_data[$field_name] : NULL;
                    $form_data[$field_name] = ( is_null($form_data[$field_name]) && $field_not_null) ? date('Y-m-d H:i:s') : $form_data[$field_name];
                    break;
                case 'int':
                    $tmp_field_val = $form_data[$field_name];
                    $form_data[$field_name] = ( filter_var($tmp_field_val, FILTER_VALIDATE_INT) === 0 || !filter_var($tmp_field_val, FILTER_VALIDATE_INT) === false ) ? (int) $form_data[$field_name] : Null;
                    break;
                default:
                    break;
            }

            $form_data[$field_name] = ( is_null($form_data[$field_name]) && $field_not_null) ? 0 : $form_data[$field_name];

            if ($field_length) {
                $form_data[$field_name] = substr($form_data[$field_name], 0, $field_length);
            }
        }

        $slug = (!isset($json['slug']) || $json['slug'] == '') ? '{{ id }}' : $json['slug'];
        $new_slug = $factory->renderString($slug, $form_data);

        if (trim($new_slug) !== '') {
            $form_data['slug'] = $string->friendlyUrl($new_slug);
        }

        return $form_data;
    }

    function validateDate($date, $format_str) {

        $date_str = ($format_str <> '') ? $format_str : 'Y-m-d H:i:s';

        $d = \DateTime::createFromFormat($date_str, $date);

        return $d && $d->format($date_str) === $date;
    }

    //xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx End of Validation

    public function saveMultiple($form_data, $id) {

        $defaultfield = new DefaultField();
        $json = $this->getJson();

        foreach ($json['fields'] as $field) {

            if ($field['parameters']['save']['saving_type'] == 'multiple') {
                $defaultfield->saveMultiples($field, $form_data, $id);
            }
        }
    }

    public function getEntityObject($id = '') {

        $entityManager = $this->container->get('doctrine')->getEntityManager();

        $form_data = ($this->form_data <> '') ? $this->form_data : $this->request->request->get('form');
        $document = $this->container->get('document');
        $extension_path = $document->extension_path;

        $id = (isset($form_data['id']) && (int) $form_data['id']) ? $form_data['id'] : $id;

        $class_name_arr = explode('/', $extension_path);
        $path_arr_rev = array_reverse($class_name_arr);
        $class_name_arr[] = 'Code';
        $class_name_arr[] = 'Tables';
        $class_name_arr[] = $path_arr_rev[0];
        $class_name = implode('\\', $class_name_arr);

        if (!$data_entity = $entityManager->getRepository($class_name)->findOneBy(array('id' => $id))) {
            $data_entity = new $class_name;
        }

        return $data_entity;
    }

    public function saveRecordByEntity($table_name, $data, $where_arr = array(), $parameter_arr = array()) {

        $entityManager = $this->container->get('doctrine')->getEntityManager();

        $strip_table_name = str_replace('#__', '', $table_name);
        $class_name_array = explode('_', $strip_table_name);
        $last_name = end($class_name_array);
        $class_name_array[] = 'code';
        $class_name_array[] = 'tables';
        $class_name_array[] = $last_name;
        $class_name_array = array_map('ucfirst', $class_name_array);

        $data_arr = (is_array($data)) ? $data : json_decode(json_encode($data), true);
        $parameter_arr = (is_array($parameter_arr)) ? $parameter_arr : json_decode(json_encode($parameter_arr), true);
        $class_name = implode('\\', $class_name_array);

        if (isset($data_arr['id']) && $data_arr['id']) {
            if (!$entity = $entityManager->getRepository($class_name)->find((int) $data_arr['id'])) {
                $entity = new $class_name();
            }
        } elseif (!empty($parameter_arr)) {
            if (!$entity = $entityManager->getRepository($class_name)->findOneBy($parameter_arr)) {
                $entity = new $class_name();
            }
        } else {
            $entity = new $class_name();
        }

        $this->saveEntity($entity, $data);

        return $entity->getId();
    }

    public function saveRecord($table_name, $data, $where_arr = array(), $parameter_arr = array()) {

        $is_update = false;
        $is_insert = false;

        $data_arr = (is_array($data)) ? $data : json_decode(json_encode($data), true);
        $parameter_arr = (is_array($parameter_arr)) ? $parameter_arr : json_decode(json_encode($parameter_arr), true);
        $table_alias = $this->getTableAlias($table_name);

        if (isset($data_arr['id']) && $data_arr['id']) {
            $is_update = true;
            $where_arr[] = $table_alias . '.id=:id';
            $parameter_arr = array('id' => $data_arr['id']);
        } elseif (!empty($where_arr)) {
            $is_update = true;
        } else {
            $is_insert = true;
        }

        if (!empty($where_arr)) {

            $query = $this->getQueryBuilder($table_name, $table_alias, $where_arr, $parameter_arr);
            $query->select('COUNT(*) AS total');
            $count = $query->loadResult();
            $is_insert = (!$count) ? true : false;
        }

        $data_arr['is_modified'] = ( isset($data_arr['is_modified']) ) ? $data_arr['is_modified'] : 1;

        $data_arr = $this->validateForm($data_arr, $table_name);

        if ($is_insert) {

            if (isset($data_arr['id'])) {
                unset($data_arr['id']);
            }

            $id = $this->insertRecord($table_name, $data_arr);
        } else {
            $id = $this->updateRecord($table_name, $data_arr, $where_arr, $parameter_arr);
        }

        return $id;
    }

    public function updateRecord($table_name, $data_arr, $where_arr = array(), $parameter_arr = array()) {

        $query = new Query();

        $user = $this->getUser();
        $user_id = (is_object($user) && $user->id) ? $user->id : 0;

// $record = $this->getRecord($table_name, $data_arr, $where_arr , $parameter_arr);
        $data_arr = (is_array($data_arr)) ? $data_arr : json_decode(json_encode($data_arr), true);
        $parameter_arr = (is_array($parameter_arr)) ? $parameter_arr : json_decode(json_encode($parameter_arr), true);
        $table_alias = $this->getTableAlias($table_name);

        $fields = $query->tableColumnsArray($table_name);
        $query->update($table_name, $table_alias);

        if (!empty($data_arr)) {
            foreach ($data_arr as $key => $data) {
                if (in_array($key, $fields)) {
                    $query->set($key, ':' . $key);
                    $query->setParameter($key, $data);
                }
            }
        }

        $query->set('modified_by', ':modified_by');
        $query->setParameter('modified_by', $user_id);
        $query->set('date_modified', ':date_modified');
        $query->setParameter('date_modified', date('Y-m-d H:i:s'));

        $this->addWhereToQuery($query, $where_arr, $parameter_arr, true);

        $query->execute();

        if (isset($data_arr['id'])) {
            return $data_arr['id'];
        } else {
            $record = $this->getQueryedRecord($table_name, $table_alias, $where_arr, $parameter_arr);
            return $record->id;
        }

        return (isset($data_arr['id'])) ? $data_arr['id'] : 0;
    }

    public function insertRecord($table_name, $data_arr) {

        $query = new Query();

        $user = $this->getUser();
        $user_id = (isset($user) && $user->id) ? $user->id : 0;
        $fields = $query->tableColumnsArray($table_name);
        $table_alias = $this->getTableAlias($table_name);

        $data_arr = (is_array($data_arr)) ? $data_arr : json_decode(json_encode($data_arr), true);

        $query->insert($table_name, $table_alias);

        if (!empty($data_arr)) {
            foreach ($data_arr as $key => $data) {
                if (in_array($key, $fields)) {
                    $query->setValue($key, ':' . $key);
                    $query->setParameter($key, $data);
                }
            }
        }

        $query->setValue('created_by', ':created_by');
        $query->setParameter('created_by', (int) $user->id);

        $query->setValue('date_created', ':date_created');
        $query->setParameter('date_created', date('Y-m-d H:i:s'));

        $query->setValue('modified_by', ':modified_by');
        $query->setParameter('modified_by', (int) $user->id);

        $query->setValue('date_modified', ':date_modified');
        $query->setParameter('date_modified', date('Y-m-d H:i:s'));

        $id = $query->executeUpdate();

        return $id;
    }

    public function deleteRecords($table_name, $where_arr = array(), $parameter_arr = array()) {

        $query = new Query();
        $table_alias = $this->getTableAlias($table_name);
        $query->delete($table_name);

        $this->addWhereToQuery($query, $where_arr, $parameter_arr, false);

        $query->execute();

        return;
    }

    public function saveEntity($entity, $data) {

        $user = $this->getUser();
        $entityManager = $this->container->get('doctrine')->getEntityManager();

        $user_id = (isset($user) && isset($user->id) && $user->id) ? $user->id : 0;

        $tmp_arr = (is_array($data)) ? $data : json_decode(json_encode($data), true);

        $tmp_arr['is_modified'] = ( isset($tmp_arr['is_modified']) ) ? $tmp_arr['is_modified'] : 1;

        $class_name = get_class($entity);
        $classMetaData = $entityManager->getClassMetadata($class_name);
        $table_name = $classMetaData->getTablename();

        $tmp_arr = $this->validateForm($tmp_arr, $table_name);

        $this->bindDataToEntity($entity, $tmp_arr);

        if ($entity->getId()) {

            $entity->setModifiedBy($user_id);
            $entity->setDateModified(new \DateTime('NOW'));

            $entityManager->merge($entity);
        } else {

            $entity->setCreatedBy($user_id);
            $entity->setDateCreated(new \DateTime('NOW'));

            $entityManager->persist($entity);
        }

        $entityManager->flush();
        $entityManager->clear();

        $entityManager->getConnection()->close();
    }

    public function bindDataToEntity($entity, $data) {

        $factory = new \Kazist\KazistFactory();
        $docReader = new AnnotationReader();
        $reflect = new \ReflectionClass($entity);
        $tmp_arr = (is_array($data)) ? $data : json_decode(json_encode($data), true);

        if (isset($tmp_arr['id']) && $tmp_arr['id']) {
            $entity->setIdManually($tmp_arr['id']);
            unset($tmp_arr['id']);
        }

        if (!empty($tmp_arr)) {
            foreach ($tmp_arr as $key => $data_value) {

                if ($key == 'id') {
                    continue;
                }

                if ($reflect->hasProperty($key)) {

                    $docInfos = $docReader->getPropertyAnnotations($reflect->getProperty($key));
                    $mysql_type = $docInfos[0]->type;

                    switch ($mysql_type) {
                        case 'datetime':
                            $data_value = ($this->validateDate($data_value, 'Y-m-d H:i:s')) ? new \DateTime($data_value) : Null;
                            break;
                        case 'date':
                            $data_value = ($this->validateDate($data_value, 'Y-m-d')) ? new \DateTime($data_value) : Null;
                            break;
                        case 'time':
                            $data_value = ($this->validateDate($data_value, 'H:i:s')) ? new \DateTime($data_value) : Null;
                            break;
                        case 'array':
                            $data_value = unserialize($data_value);
                            break;
                    }

                    $key_arr = explode('_', $key);
                    $key_arr = array_map('ucfirst', $key_arr);
                    $function_name = 'set' . implode('', $key_arr);

                    $entity->$function_name($data_value);
                } else {
                    $factory->loggingMessage('Error: the entity does not have a such ' . $key . ' property', 'error');
                }
            }
        }

        return $entity;
    }

    public function getQueryBuilder($table_name, $table_alias, $where_arr = array(), $parmeter_arr = array(), $ordering_arr = array(), $offset = 0, $limit = 10) {

        $query = new Query();

        $json = $this->getJson($table_name);

        $this->table_alias = ($table_alias <> '') ? $table_alias : $this->getTableAlias($table_name);

        if (!empty($json)) {

            $from_alias = $json['query']['from']['alias'];
            $this->table_alias = ($table_alias != '') ? $table_alias : $from_alias;
            $table_name = $json['query']['from']['name'];
            $table_leftjoin = (isset($json['query']['leftjoin'])) ? $json['query']['leftjoin'] : '';

            $table_select = array_map(function($val) {
                $val = str_replace('. ', '.', $val);
                $val = str_replace('_ ', '_', $val);
                return $val;
            }, $json['query']['select']);

            if ($table_alias <> '' && $from_alias <> $table_alias) {
                foreach ($table_select as $key => $value) {
                    $table_select[$key] = str_replace($from_alias . '.', $this->table_alias . '.', $value);
                }
            }

            $table_select[] = $this->table_alias . '.slug';
            $table_select[] = $this->table_alias . '.is_modified';
            $table_select[] = $this->table_alias . '.system_tracking_id';

            $query->from($table_name, $this->table_alias);

            $query->select(implode(',', $table_select));

            if (!empty($table_leftjoin)) {
                foreach ($table_leftjoin as $leftjoin) {
                    $leftjoin_on = str_replace($from_alias . '.', $this->table_alias . '.', $leftjoin['on']);
                    $query->leftJoin($this->table_alias, $leftjoin['name'], $leftjoin['alias'], $leftjoin_on);
                }
            }
        } elseif ($table_name <> '') {
            $this->table_alias = ($table_alias != '') ? $table_alias : $this->table_alias;

            $query->from($table_name, $table_alias);
            $query->select($table_alias . '.*');
        }

        $this->addWhereToQuery($query, $where_arr, $parmeter_arr);
        $this->addOrderingToQuery($query, $ordering_arr);

        return $query;
    }

    private function addOrderingToQuery($query, $ordering_arr) {

        if (!empty($ordering_arr)) {

            $is_first = true;

            foreach ($ordering_arr as $key => $ordering) {

                $field_name = (is_int($key)) ? $ordering : $key;
                $field_ordering = (!is_int($key)) ? $ordering : $this->ordering;

                if ($is_first) {
                    $query->orderBy($field_name, $field_ordering);
                } else {
                    $query->addOrderBy($field_name, $field_ordering);
                }

                $is_first = false;
            }
        }
        return $query;
    }

    public function addWhereToQuery($query, $where_arr, $parameter_arr, $add_alias = true) {

        if (!empty($parameter_arr)) {
            foreach ($parameter_arr as $key => $parameter) {
                $query->setParameter($key, $parameter);
            }
        }

        if (!empty($where_arr)) {
            foreach ($where_arr as $key => $where) {

                if ($add_alias && !(int) strpos($where, 'AND') && !(int) strpos($where, 'OR')) {

                    $where_sub_arr = explode('=', $where);
                    $field_name = $where_sub_arr[0];
                    $field_name_arr = explode('.', $field_name);

                    $factory = new \Kazist\KazistFactory();
                    $factory->loggingMessage($where_sub_arr);
                    $factory->loggingMessage($where_sub_arr[0]);

                    if (!is_numeric($where_sub_arr[0])) {
                        $new_field_name = (!strpos($field_name, '.')) ? $this->table_alias . '.' . $field_name_arr[0] : $field_name;
                        $where = $new_field_name . '=' . $where_sub_arr[1];
                    }
                }

                if (!$key) {
                    $query->where($where);
                } else {
                    $query->andwhere($where);
                }
            }
        }

        return $query;
    }

    public function appendSearchQuery($query) {

        $document = $this->container->get('document');
        $search = $document->search;

        $where_arr = array();
        $sub_where_arr = array();
        $parameter_arr = array();

        $json = $this->getJson();

        if (!empty($search)) {

            $keyword = $search['keyword'];
            unset($search['keyword']);

            if ($keyword <> '') {

                $select_arr = $json['query']['select'];

                foreach ($select_arr as $select) {
                    $select_name_arr = explode(' AS ', $select);
                    $sub_where_arr[] = $select_name_arr[0] . ' LIKE :keyword';
                }

                $parameter_arr['keyword'] = '%' . $keyword . '%';
                $where_arr[] = implode(' OR ', $sub_where_arr);
            }

            foreach ($search as $key => $item) {
                if (isset($json['fields'][$key])) {

                    $field = $json['fields'][$key];

                    if ($item != '' && !is_array($item)) {
                        $where_arr[] = $this->table_alias . '.' . $key . ' = :' . $key;
                        $parameter_arr[$key] = $item;
                    } elseif (is_array($item)) {
                        if ($this->validateDate($item['start'], $field['html_type']) && $this->validateDate($item['end'], $field['html_type'])) {
                            $where_arr[] = $this->table_alias . '.' . $key . ' BETWEEN :' . $key . '_start AND :' . $key . '_end';
                            $parameter_arr[$key . '_start'] = $item['start'];
                            $parameter_arr[$key . '_end'] = $item['end'];
                        } elseif ($this->validateDate($item['start'], $field['html_type'])) {
                            $where_arr[] = $this->table_alias . '.' . $key . ' > :' . $key . '_start';
                            $parameter_arr[$key . '_start'] = $item['start'];
                        } elseif ($this->validateDate($item['end'], $field['html_type'])) {
                            $where_arr[] = $this->table_alias . '.' . $key . ' < :' . $key . '_end';
                            $parameter_arr[$key . '_end'] = $item['end'];
                        }
                    }
                }
            }
        }

        $this->addWhereToQuery($query, $where_arr, $parameter_arr, false);

        return $query;
    }

    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH, $data = array()) {

        $tmp_data = json_decode(json_encode($data), true);
        $tmp_parameters = json_decode(json_encode($parameters), true);
        $data_arr = array_merge((array) $tmp_data, (array) $tmp_parameters);

        $routes = $this->container->getParameter('routes');
        $route_obj = $routes->get($route);
        $defaults = $route_obj->getDefaults();

        $controller = $defaults['_controller'];
        $controller_arr = explode('Code', $controller);
        $extension_path = str_replace('\\', '/', rtrim($controller_arr[0], '\\'));


        if (!WEB_IS_ADMIN) {

            if ($extension_path <> '' && $parameters['id'] && array_key_exists("slug", $defaults)) {

                $table_name = '#__' . str_replace('/', '_', strtolower($extension_path));

                $query = new Query();
                $query->select('tx.slug');
                $query->from($table_name, 'tx');
                $query->where('tx.id=:id');
                $query->setParameter('id', $parameters['id']);
                $record = $query->loadObject();

                if ($record->id && $record->slug == '') {

                    $query = new Query();
                    $query->select('tx.*');
                    $query->from($table_name, 'tx');
                    $query->where('tx.id=:id');
                    $query->setParameter('id', $parameters['id']);
                    $record = $query->loadObject();

                    $record_arr = json_decode(json_encode($record), true);
                    $tmp_record = $this->updateSlug($route, $record_arr);
                } else {
                    if (array_key_exists("slug", $defaults)) {
                        unset($parameters['id']);
                    }

                    $tmp_record = json_decode(json_encode($record), true);
                }

                $data_arr = array_merge((array) $data_arr, (array) $tmp_record);

                unset($data_arr['id']);
            }
        }

        if (array_key_exists('slug', $data_arr) && $data_arr['slug'] == '') {
            $data_arr = $this->updateSlug($route, $data_arr);
        }

        return parent::generateUrl($route, $parameters, $referenceType, $data_arr);
    }

    public function updateSlug($route, $data) {

        $string = new StringModification();

        $route = $this->getQueryedRecord('#__system_routes', 'sr', array('unique_name=:unique_name'), array('unique_name' => $route));

        if ($route->extension_path <> '') {

            $table_name = str_replace('/', '_', strtolower($route->extension_path));

            $new_slug = $string->underscore($data['title'] . ' ' . $data['name']);

            $updated_slug = str_replace('_', '-', $new_slug);

            $data_obj = new \stdClass();
            $data_obj->id = $data['id'];
            $data_obj->slug = $updated_slug;

            $id = $this->saveRecord('#__' . $table_name, $data_obj);

            $data['slug'] = $updated_slug;
        }

        return $data;
    }

    public function getJson($table_name = '') {

        $query = new Query();
        $controller = $this->request->attributes->get('_controller');

        if ($table_name) {

            $tmp_table_name = str_replace('#__', '', $table_name);

            if ($tmp_table_name == $table_name) {
                $table_name = $query->removePrefix($table_name);
            } else {
                $table_name = $tmp_table_name;
            }

            $table_name = str_replace('#__', '', $table_name);
            $table_name = $query->removePrefix($table_name);

            $table_name_arr = explode('_', $table_name);
            $table_name_arr = array_map('ucfirst', $table_name_arr);

            $path = JPATH_ROOT . 'applications/' . implode('/', $table_name_arr) . '/Code/structure.json';
        } else {
            $controller_arr = explode('Code', $controller);

            $sub_path = str_replace('\\', '/', $controller_arr[0]);

            $path = JPATH_ROOT . 'applications/' . $sub_path . '/Code/structure.json';
        }

        $json = (file_exists($path)) ? $this->jsonStructure($path) : false;

        return $json;
    }

    public function getDetailedJson($table_name = '', $id = '') {

        $defaultfield = new DefaultField();

        $exempt_arr = array('text', 'textarea');
        $id = ($id) ? $id : $this->container->get('request')->get('id');
        $extension_path = $this->container->get('document')->extension_path;

        $table_name = '#__' . str_replace('/', '_', strtolower($extension_path));

        $item = $this->getRecord($id);

        $json = $this->getJson($table_name);


        foreach ($json['fields'] as $field_name => $field) {

            if ($field['dropdown_filter'] && !in_array($field['html_type'], $exempt_arr)) {
                $json['views']['advancesearch'] = true;
            }

            if (!isset($field['view_side']) || $field['view_side'] == '') {
                $json['fields'][$field_name]['view_side'] = 'both';
            }

            $json['fields'][$field_name]['default']['edit'] = $defaultfield->getEditData($field, $item);
            $json['fields'][$field_name]['default']['detail'] = $defaultfield->getDetailData($field, $item);
            $json['fields'][$field_name]['parameters']['options'] = $defaultfield->getOptionData($field, $item);
        }

        return $json;
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

//xxxxxxxxxxxxxxxxxxxxxxxxxxxxx  Auto Complete  Actions

    public function getAjaxAutocomplete() {

        $document = $this->container->get('document');

        $tmp_array = array();
        $keyword = $this->request->get('keyword');
        $join_field = $this->request->get('join_field');
        $display_field = $this->request->get('display_field');
        $display_field_arr = explode('|', $display_field);
        $extension_path = $document->extension_path;
        $table_name = '#__' . str_replace('/', _, strtolower($extension_path));

        $display_field_arr = explode('|', $display_field);

        $query = new Query();
        $query->select($join_field . ',' . str_replace('|', ',', $display_field));
        $query->from($table_name);

        if ($keyword <> '') {
            foreach ($display_field_arr as $field) {
                $query->orWhere(trim($field) . ' LIKE :' . trim($field));
                $query->setParameter(trim($field), '%' . $keyword . '%');
            }
        } else {
            $query->where('1=-1');
        }

        $records = $query->loadObjectList();


        foreach ($records as $key => $record) {

            $value = $record->$join_field;
            $text = array();

            foreach ($display_field_arr as $field) {
                $field_name = ltrim($field, ' ');
                $text[] = $record->$field_name;
            }

            $tmp_array[] = array('value' => $value, 'label' => implode(' : ', $text));
        }

        return $tmp_array;
    }

    public function getAjaxAutocompleteDefault() {

        $document = $this->container->get('document');

        $text_str = array();
        $default_value = $this->request->get('default_value');
        $join_field = $this->request->get('join_field');
        $display_field = $this->request->get('display_field');
        $display_field_arr = explode('|', $display_field);
        $extension_path = $document->extension_path;
        $table_name = '#__' . str_replace('/', _, strtolower($extension_path));

        if ($default_value <> '') {


            $query = new Query();
            $query->select($join_field . ',' . str_replace('|', ',', $display_field));
            $query->from($table_name);
            $query->where($join_field . ' = :' . $join_field);
            $query->setParameter($join_field, $default_value);

            $record = $query->loadObject();

            foreach ($display_field_arr as $field) {
                $field_name = ltrim($field, ' ');
                $text_str[] = $record->$field_name;
            }

            return (!empty($text_str)) ? implode(' : ', $text_str) : '';
        }

        return '';
    }

    public function autoInstallTable($extension_path) {

        $this->doctrine->refresh = true;
        $this->doctrine->entity_path = JPATH_ROOT . 'applications/' . $extension_path . '/Code/Tables';

        if (is_dir($this->doctrine->entity_path)) {
            $this->doctrine->getEntityManager();
        }
    }

}
