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
use Kazist\KazistFactory;
use Kazist\Service\Database\Query;
use \Symfony\Component\Security\Core\User\UserInterface;
use \Symfony\Component\Security\Core\User\UserProviderInterface;
use \Symfony\Component\Security\Core\Exception as SecurityException;

class UserProvider implements UserProviderInterface {

    public function loadUserByUsername($username) {
        try {

            $user = $this->readUserFromDatabase($username);

            if ($user instanceof User) {
                return $user;
            }
        } catch (DatabaseException $e) {
            throw new SecurityException\AuthenticationServiceException($e->getMessage());
        }
        throw new SecurityException\UsernameNotFoundException();
    }

    public function refreshUser(UserInterface $user) {
        if (!$user instanceof User) {
            throw new SecurityException\UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class) {
        return $class === 'User';
    }

    public function readUserFromDatabase($username) {

        global $sc;

        $user_obj = new User();
        $factory = new KazistFactory();
        $query = new Query();

        try {

            $query->select('uu.*');
            $query->from('#__users_users', 'uu');
            $query->where('uu.username = :username OR uu.email = :email');
            $query->setParameter('username', $username);
            $query->setParameter('email', $username);
            $user = $query->loadObject();

            $user_obj->id = $user->id;
            $user_obj->email = $user->email;
            $user_obj->password = $user->password;
            $user_obj->username = $user->username;
            $user_obj->email = $user->email;
            $user_obj->salt = $user->salt;

            if (!is_object($user)) {
                $user_obj = false;
                $factory->enqueueMessage('No User Found With that username/email.', 'error');
            } elseif (!$user->is_verified) {
                $user_obj = false;
                $resend_link=$factory->generateUrl('resend_verification_form');
                $verification_msg='Your Account is not yet Verified. '
                        . 'Please go to your inbox and click verification link.<br>'
                        . 'Resend the Verification Code <a class="btn btn-primary" href="'.$resend_link.'">Resend Verification Link</a>';
                $factory->enqueueMessage($verification_msg, 'error');
            } elseif (!$user->published) {
                $user_obj = false;
                $factory->enqueueMessage('Your Account is Blocked. Please contact Admin.', 'error');
            }

            return $user_obj;
        } catch (Exception $ex) {
            throw new $e;
        }
    }

}
