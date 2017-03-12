<?php

/*
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

namespace Kazist\Service\Security;

/**
 * Description of User
 *
 * @author sbc
 */
use \Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface  {

    public $id;
    public $email;
    public $password;
    public $username;
    public $salt;

    public function eraseCredentials() {
        $this->password = '';
    }

    public function getRoles() {
        return array('ROLE_USER','ROLE_SUPER_ADMIN','ROLE_ADMIN');
    }

    public function getSalt() {
        return $this->salt;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }


}
