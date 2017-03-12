<?php

/*
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

namespace Kazist\Service\Security;

/**
 * Description of UserChecker
 *
 * @author sbc
 */
use Symfony\Component\Security\Core\User\UserChecker;
use \Symfony\Component\Security\Core\User\UserInterface;

class NewUserChecker extends UserChecker {

    public function checkPostAuth(UserInterface $user) {
        parent::checkPostAuth($user);
    }

    public function checkPreAuth(UserInterface $user) {
        parent::checkPreAuth($user);
    }

}
