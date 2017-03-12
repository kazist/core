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

namespace Kazist\Event;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\EventDispatcher\Event;


class CRUDEvent extends Event {

    private $record;

    public function __construct($record) {
        $this->record = $record;
    }

    public function getRecord() {
        return $this->record;
    }

}
