<?php

/*
 * This file is part of Kazist Framework.
 * (c) Dedan Irungu <irungudedan@gmail.com>
 *  For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 * 
 */

/**
 * Description of PermissionVoter
 *
 * @author sbc
 */
// src/AppBundle/Security/PostVoter.php

namespace Kazist\Service\Security\Voter;

use Kazist\Service\Security\User;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter {

    //put your code here
    // these strings are just invented: you can use anything
    const VIEW = 'can_view';
    const WRITE = 'can_write';
    const DELETE = 'can_delete';
    const VIEWOWN = 'can_viewown';
    const WRITEOWN = 'can_writeown';
    const DELETEOWN = 'can_deleteown';

    private $container = '';
    private $document = '';
    private $user = '';
    private $object = '';
    private $subset_permissions = '';

    protected function supports($attribute, $object) {

        $new_attribute = strtolower($attribute);

        // if the attribute isn't one we support, return false
        if (!in_array($new_attribute, array(self::VIEW, self::WRITE, self::DELETE, self::VIEWOWN, self::WRITEOWN, self::DELETEOWN))) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $object, TokenInterface $token) {
        global $sc;

        $this->object = $object;
        $this->container = $sc;
        $this->document = $this->container->get('document');
        $this->user = $token->getUser();

        if (!$this->user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($this->container->get('security.access_decision')->decide($token, array('ROLE_SUPER_ADMIN', 'ROLE_ADMIN'))) {
            return true;
        }

        $this->prepareUserRoles();

        switch ($attribute) {
            case self::VIEW:
                return $this->canView();
            case self::WRITE:
                return $this->canWrite();
            case self::DELETE:
                return $this->canDelete();
            case self::VIEWOWN:
                return $this->canViewOwn();
            case self::WRITEOWN:
                return $this->canWriteOwn();
            case self::DELETEOWN:
                return $this->canDeleteOwn();
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView() {

        if ($this->subset_permissions['can_view']) {
            return true;
        }
    }

    private function canWrite() {
        if ($this->subset_permissions['can_write']) {
            return true;
        }
    }

    private function canDelete() {
        if ($this->subset_permissions['can_delete']) {
            return true;
        }
    }

    private function canViewOwn() {
        if ($this->subset_permissions['can_viewown']) {
            return true;
        }
    }

    private function canWriteOwn() {
        if ($this->subset_permissions['can_writeown']) {
            return true;
        }
    }

    private function canDeleteOwn() {
        if ($this->subset_permissions['can_deleteown']) {
            return true;
        }
    }

    private function prepareUserRoles() {

        $entityManager = $this->container->get('doctrine')->getEntityManager();

        if (!$this->user->id) {
            if ($user_obj = $entityManager->getRepository("Users\Users\Code\Tables\Users")->findOneBy(array('email' => $this->user->email))) {
                $this->user->id = $user_obj->getId();
            }
        }

        $roles = $this->user->getRoles();

        $roles = array_map(function($value) {
            return strtoupper(str_replace('role_', '', strtolower($value)));
        }, $roles);

        $this->subset_permissions = $entityManager->createQueryBuilder()
                ->from('Users\Permission\Code\Tables\Permission', 'p')
                // ->select('r.alias, p.can_add, p.can_view, p.can_write, p.can_delete, p.can_viewown, p.can_writeown, p.can_deleteown')
                ->select('MAX(p.can_add) AS can_add, MAX(p.can_view) AS can_view, MAX(p.can_write) AS can_write, MAX(p.can_delete) AS can_delete, MAX(p.can_viewown) AS can_viewown, MAX(p.can_writeown) AS can_writeown, MAX(p.can_deleteown) AS can_deleteown')
                ->leftJoin('Users\Roles\Code\Tables\Roles', 'r', Join::WITH, 'p.role_id = r.id')
                ->where('r.alias IN (:roles)')
                ->andWhere('p.subset_id = :subset_id')
                ->setParameter('roles', $roles)
                ->setParameter('subset_id', $this->document->getSubsetId())
                ->getQuery()
                ->useQueryCache(true)
                ->useResultCache(true)
                ->setResultCacheLifetime(3600)
                ->getOneOrNullResult();
    }

}
