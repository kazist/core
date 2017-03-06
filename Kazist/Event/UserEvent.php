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

class UserEvent extends Event {

    private $user;

    public function __construct($user) {
        $this->user = $user;
    }

    public function getUser() {
        return $this->user;
    }

}

/**
 * Some of Events to be fired
 * - user.before.registration
 * - user.after.registration
 * - user.before.login
 * - user.after.login
 * - user.before.save
 * - user.after.save
 * - user.before.delete
 * - user.after.delete
 * 
 */