<?php

/*
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

namespace Kazist\Service\Security\Encoder;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\KazistFactory;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Description of MD5Encoder
 *
 * @author sbc
 */
class HashedMD5Encoder extends BasePasswordEncoder {

    //put your code here

    public function encodePassword($raw, $salt) {

        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }
    }

    public function isPasswordValid($encoded, $raw, $salt) {

        $factory = new KazistFactory();
        $session = $factory->getSession();

        $is_remotelogin = $session->get('is_remotelogin');

        if ($is_remotelogin) {
            return true;
        }
        
        if ($this->isPasswordTooLong($raw)) {
            return false;
        }

        if ($encoded == md5($raw)) {
            return true;
        } elseif ($salt <> '' && $encoded == md5(md5($raw) . md5($salt))) {
            return true;
        }

        return false;
    }

}
