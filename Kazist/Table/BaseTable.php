<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Table;

use Doctrine\ORM\Mapping as ORM;
use Kazist\Event\CRUDEvent;

/**
 * Description of BaseController
 * @author sbc
 */
class BaseTable {

    /**
     * @var integer
     *
     * @ORM\Column(name="system_tracking_id", type="integer", length=11,  nullable=true)
     */
    protected $system_tracking_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_modified", type="integer", length=11,  nullable=true)
     */
    protected $is_modified;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255,  nullable=true)
     */
    protected $slug;
    protected $tmp_id;

    /**
     * Set slug
     *
     * @param string $slug
     * @return Subsets
     */
    public function setSlug($slug) {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Set is_modified
     *
     * @param integer $is_modified
     * @return Subsets
     */
    public function setIsModified($is_modified) {
        $this->is_modified = $is_modified;

        return $this;
    }

    /**
     * Get is_modified
     *
     * @return integer 
     */
    public function getIsModified() {
        return $this->is_modified;
    }

    /**
     * Set system_tracking_id
     *
     * @param integer $system_tracking_id
     * @return Subsets
     */
    public function setSystemTrackingId($system_tracking_id) {
        $this->system_tracking_id = $system_tracking_id;

        return $this;
    }

    /**
     * Get system_tracking_id
     *
     * @return integer 
     */
    public function getSystemTrackingId() {
        return $this->system_tracking_id;
    }

    /**
     * Set created_by
     *
     * @param integer $createdBy
     * @return Extensions
     */
    public function setCreatedBy($createdBy) {

        $createdBy = ($createdBy) ? $createdBy : 1;

        $this->created_by = $createdBy;

        return $this;
    }

    /**
     * Set date_created
     *
     * @param \DateTime $dateCreated
     * @return Extensions
     */
    public function setDateCreated($dateCreated) {

        if (!is_a($dateCreated, 'DateTime') || $dateCreated->format('Y') < 0) {
            $dateCreated = new \DateTime('now');
        }

        $dateCreated = ($dateCreated) ? $dateCreated : date('Y-m-d H:i:s');

        $this->date_created = $dateCreated;

        return $this;
    }

    /**
     * Set modified_by
     *
     * @param integer $modifiedBy
     * @return Extensions
     */
    public function setModifiedBy($modifiedBy) {

        $modifiedBy = ($modifiedBy) ? $modifiedBy : 1;

        $this->modified_by = $modifiedBy;

        return $this;
    }

    /**
     * Set date_modified
     *
     * @param \DateTime $dateModified
     * @return Extensions
     */
    public function setDateModified($dateModified) {

        if (!is_a($dateModified, 'DateTime') || $dateModified->format('Y') < 0) {
            $dateModified = new \DateTime('now');
        }

        $dateModified = ($dateModified) ? $dateModified : date('Y-m-d H:i:s');

        $this->date_modified = $dateModified;

        return $this;
    }

    public function setIdManually($id) {

        $this->id = $id;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist() {

        $createdBy = $this->getCreatedBy();
        $modifiedBy = $this->getModifiedBy();
        $dateCreated = $this->getDateCreated();
        $dateModified = $this->getDateModified();

        $newDateCreated = ($dateCreated !== null) ? $dateCreated : new \DateTime();
        $newDateModified = ($dateModified !== null) ? $dateModified : new \DateTime();
        $newCreatedBy = ($createdBy !== null) ? $createdBy : 1;
        $newModifiedBy = ($modifiedBy !== null) ? $modifiedBy : 1;

        $this->setDateCreated($newDateCreated);
        $this->setDateModified($newDateModified);
        $this->setCreatedBy($newCreatedBy);
        $this->setModifiedBy($newModifiedBy);
    }

    /**
     * @ORM\PreRemove
     */
    public function onPreDelete() {
        $this->tmp_id = $this->getId();
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersistCallbacks() {
        global $sc;

        $document = $sc->get('document');

        if (isset($document->extension_path) && $document->extension_path <> '') {

            $name = str_replace('/', '.', strtolower($document->extension_path)) . '.before.save';

            $record = clone $this;

            $sc->get('dispatcher')->dispatch($name, new CRUDEvent($record));
        }
    }

    /**
     * @ORM\PostPersist
     */
    public function onPostPersistCallbacks() {
        global $sc;

        $document = $sc->get('document');

        if (isset($document->extension_path) && $document->extension_path <> '') {

            $name = str_replace('/', '.', strtolower($document->extension_path)) . '.after.save';
            $record = clone $this;

            $sc->get('dispatcher')->dispatch($name, new CRUDEvent($record));
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdateCallbacks() {
        global $sc;

        $document = $sc->get('document');

        if (isset($document->extension_path) && $document->extension_path <> '') {

            $name = str_replace('/', '.', strtolower($document->extension_path)) . '.before.update';

            $record = clone $this;

            $sc->get('dispatcher')->dispatch($name, new CRUDEvent($record));
        }
    }

    /**
     * @ORM\PostUpdate
     */
    public function onPostUpdateCallbacks() {
        global $sc;

        $document = $sc->get('document');

        if (isset($document->extension_path) && $document->extension_path <> '') {

            $name = str_replace('/', '.', strtolower($document->extension_path)) . '.after.update';

            $record = clone $this;

            $sc->get('dispatcher')->dispatch($name, new CRUDEvent($record));
        }
    }

    /**
     * @ORM\PreRemove
     */
    public function onPreDeleteCallbacks() {
        global $sc;

        $document = $sc->get('document');

        if (isset($document->extension_path) && $document->extension_path <> '') {
            $name = str_replace('/', '.', strtolower($document->extension_path)) . '.before.delete';

            $record = clone $this;

            $sc->get('dispatcher')->dispatch($name, new CRUDEvent($record));
        }
    }

    /**
     * @ORM\PostRemove
     */
    public function onPostDeleteCallbacks() {
        global $sc;

        $document = $sc->get('document');

        if (isset($document->extension_path) && $document->extension_path <> '') {

            $name = str_replace('/', '.', strtolower($document->extension_path)) . '.after.delete';

            $record = clone $this;
            $record->id = $this->tmp_id;


            $sc->get('dispatcher')->dispatch($name, new CRUDEvent($record));
        }
    }

}
