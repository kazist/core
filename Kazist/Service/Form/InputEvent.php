<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ResponseEvent
 *
 * @author sbc
 */

namespace Kazist\Service\Form;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\EventDispatcher\Event;

class InputEvent extends Event {

    private $form_data;
    private $input_name;
    private $mysql_type;

    public function __construct($form_data, $input_name, $mysql_type) {
        
        $this->form_data = $form_data;
        $this->input_name = $input_name;
        $this->mysql_type = $mysql_type;
        
    }

    public function getFormData() {
        return $this->form_data;
    }

    public function getInputName() {
        return $this->input_name;
    }

    public function getMysqlType() {
        return $this->mysql_type;
    }

}
