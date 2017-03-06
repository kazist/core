<?php

namespace Kazist\Service\Form;

defined('KAZIFRAMEWORK') or exit(DIE_MSG);

use Kazist\KazistFactory;
use Kazist\Service\Database\Table;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Autocomplete {

    /**
     * Save Function
     *
     * @return  boolean
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function autoComplete() {

        $table = new Table();
        $KazistFactory = new KazistFactory();
        $input = $KazistFactory->getInput();

        $tmp_array = array();
        $app_name = $this->request->get('app');
        $com_name = $this->request->get('com');
        $subset_name = $this->request->get('subset');
        $keyword = $this->request->get('keyword');
        $join_field = $this->request->get('join_field');
        $display_field = $this->request->get('display_field');
        $display_field_arr = explode('|', $display_field);

        $table_name = $table->getTableName($app_name, $com_name, $subset_name);

        $records = $this->getRecords($table_name, $join_field, $display_field, $keyword);

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

    public function getRecords($table_name, $join_field, $display_field, $keyword) {

        $KazistFactory = new KazistFactory();
        $db = $KazistFactory->getDatabase();

        $display_field_arr = explode('|', $display_field);

        $query = $db->getQuery(true);
        $query->select($join_field . ',' . str_replace('|', ',', $display_field));
        $query->from($table_name);

        if ($keyword <> '') {
            foreach ($display_field_arr as $field) {
                $query->where($db->quoteName(trim($field)) . ' LIKE ' . $db->quote('%' . $keyword . '%'), ' OR ');
            }
        } else {
            $query->where('1=-1');
        }

        $records = $db->setQuery($query, 0, 10)->loadObjectList();

        return $records;
    }

    public function autoCompleteDefault() {

        $table = new Table();
        $KazistFactory = new KazistFactory();
        $db = $KazistFactory->getDatabase();
        $input = $KazistFactory->getInput();

        $text_str = array();
        $tmp_array = array();
        $app_name = $this->request->get('app');
        $com_name = $this->request->get('com');
        $subset_name = $this->request->get('subset');
        $default_value = $this->request->get('default_value');
        $join_field = $this->request->getString('join_field');
        $display_field = $this->request->get('display_field');
        $display_field_arr = explode('|', $display_field);

        if ($default_value <> '') {

            $table_name = $table->getTableName($app_name, $com_name, $subset_name);

            $query = $db->getQuery(true);
            $query->select($join_field . ',' . str_replace('|', ',', $display_field));
            $query->from($table_name);
            $query->where($db->quoteName($join_field) . ' = ' . $db->quote($default_value));

            $record = $db->setQuery($query)->loadObject();

            foreach ($display_field_arr as $field) {
                $field_name = ltrim($field, ' ');
                $text_str[] = $record->$field_name;
            }

            return (!empty($text_str)) ? implode(' : ', $text_str) : '';
        }

        return '';
    }

}
