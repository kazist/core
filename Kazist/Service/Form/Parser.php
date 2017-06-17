<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Service\Form;

defined('KAZIST') or exit('Not Kazist Framework');

class Validation {

    public $container = '';

    function __construct() {

        global $sc;

        $this->container = $sc;
    }

    function validate($input_name, $form_data, $html_type, $mysql_type) {

        $valid_field = $this->container->get('dispatcher')->dispatch('field.' . $input_name . '.validation', new InputEvent($form_data, $input_name, $mysql_type));
        $valid_input = $this->container->get('dispatcher')->dispatch('input.' . $html_type . '.validation', new InputEvent($form_data, $input_name, $mysql_type));
        $valid_mysql = $this->container->get('dispatcher')->dispatch('mysql.' . $mysql_type . '.validation', new InputEvent($form_data, $input_name, $mysql_type));

        return ($valid_field && $valid_input && $valid_mysql) ? true : false;
    }

}
