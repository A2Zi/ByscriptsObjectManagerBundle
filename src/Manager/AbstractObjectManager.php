<?php
/*
 * This file is part of the ByscriptsManagerBundle package.
 *
 * (c) Thierry Goettelmann <thierry@byscripts.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Byscripts\Bundle\ObjectManagerBundle\Manager;

use Byscripts\Bundle\ObjectManagerBundle\Exception\ObjectManagerException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\LockMode;

/**
 * Class AbstractManager
 *
 * @author Thierry Goettelmann <thierry@byscripts.info>
 */
abstract class AbstractObjectManager
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $doctrineObjectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function setDoctrineObjectManager(ObjectManager $objectManager)
    {
        $this->doctrineObjectManager = $objectManager;
    }

    /**
     * @return ObjectRepository
     */
    abstract public function getRepository();

    /**
     * Alias to repository find method
     *
     * @param string    $id
     * @param int|mixed $lockMode
     * @param null      $lockVersion
     *
     * @return null|object
     */
    final public function find($id, $lockMode = LockMode::NONE, $lockVersion = null)
    {
        return $this->getRepository()->find($id, $lockMode, $lockVersion);
    }

    /**
     * Alias to repository findAll method
     *
     * @return array
     */
    final public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Alias to repository findBy method
     *
     * @param array $criteria
     * @param array $orderBy
     * @param null  $limit
     * @param null  $offset
     *
     * @return array
     */
    final public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->getRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Alias to repository findOneBy method
     *
     * @param array $criteria
     * @param array $orderBy
     *
     * @return null|object
     */
    final public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->getRepository()->findOneBy($criteria, $orderBy);
    }

    /**
     * Alias to object manager persist method
     *
     * @param $object
     *
     * @return $this
     */
    final public function persist($object)
    {
        $this->doctrineObjectManager->persist($object);

        return $this;
    }

    /**
     * Alias to object manager remove method
     *
     * @param $object
     *
     * @return $this
     */
    final public function remove($object)
    {
        $this->doctrineObjectManager->remove($object);

        return $this;
    }

    /**
     * Alias to object manager flush method
     *
     * @return $this
     */
    final public function flush()
    {
        $this->doctrineObjectManager->flush();

        return $this;
    }

    /**
     * Save the object to the database
     *
     * @param object $object
     *
     * @return bool
     */
    public function save($object)
    {
        $isNew = !(bool)$object->getId();

        try {
            $this->processSave($object);

            $isNew
                ? $this->onCreateSuccess($object)
                : $this->onUpdateSuccess($object);

            return true;
        } catch (ObjectManagerException $exception) {
            $isNew
                ? $this->onCreateError($object, $exception->getMessage())
                : $this->onUpdateError($object, $exception->getMessage());

            return false;
        }
    }

    /**
     * Delete the object from the database
     *
     * @param object $object
     *
     * @return bool
     */
    public function delete($object)
    {
        try {
            $this->processDelete($object);
            $this->onDeleteSuccess($object);

            return true;
        } catch (ObjectManagerException $exception) {
            $this->onDeleteError($object, $exception->getMessage());

            return false;
        }
    }

    /**
     * Duplicate the object
     *
     * @param object $object The object to duplicate
     *
     * @return object|false The new object
     */
    public function duplicate($object)
    {
        try {
            $clone = $this->processDuplicate($object);
            $this->onDuplicateSuccess($object, $clone);

            return $clone;
        } catch (ObjectManagerException $exception) {
            $this->onDuplicateError($object, $exception->getMessage());

            return false;
        }
    }

    /**
     * Activate the object
     *
     * @param object $object The object to activate
     *
     * @return bool
     */
    public function activate($object)
    {
        try {
            $this->processActivate($object);
            $this->onActivateSuccess($object);

            return true;
        } catch (ObjectManagerException $exception) {
            $this->onActivateError($object, $exception->getMessage());

            return false;
        }
    }

    /**
     * Deactivate the object
     *
     * @param object $object The object to deactivate
     *
     * @return bool
     */
    public function deactivate($object)
    {
        try {
            $this->processDeactivate($object);
            $this->onDeactivateSuccess($object);

            return true;
        } catch (ObjectManagerException $exception) {
            $this->onDeactivateError($object, $exception->getMessage());

            return false;
        }
    }

    /**
     * @param object $object
     */
    protected function processSave($object)
    {
        $this->persist($object)->flush();
    }

    /**
     * @param $object
     */
    protected function processDelete($object)
    {
        $this->remove($object)->flush();
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    protected function processDuplicate($object)
    {
        $clone = clone $object;
        $this->persist($clone)->flush();

        return $clone;
    }

    /**
     * @param object $object
     */
    protected function processActivate($object)
    {
        $object->setActive(true);
        $this->persist($object)->flush();
    }

    /**
     * @param object $object
     */
    protected function processDeactivate($object)
    {
        $object->setActivate(false);
        $this->persist($object)->flush();
    }

    /**
     * Triggered after the object is created
     *
     * @param object $object
     */
    protected function onCreateSuccess($object)
    {
    }

    /**
     * Triggered if the object can not be created
     *
     * @param object $object
     * @param string $errorMessage
     */
    protected function onCreateError($object, $errorMessage)
    {
    }

    /**
     * Triggered after the object is updated
     *
     * @param object $object
     */
    protected function onUpdateSuccess($object)
    {
    }

    /**
     * Triggered if the object can not be saved
     *
     * @param object $object
     * @param string $errorMessage
     */
    protected function onUpdateError($object, $errorMessage)
    {
    }

    /**
     * Triggered after the object is deleted
     *
     * @param object $object
     */
    protected function onDeleteSuccess($object)
    {
    }

    /**
     * Triggered after the object can not be deleted
     *
     * @param object $object
     * @param string $errorMessage
     */
    protected function onDeleteError($object, $errorMessage)
    {
    }

    /**
     * Triggered after the object is activated
     *
     * @param object $object
     */
    protected function onActivateSuccess($object)
    {
    }

    /**
     * Triggered if the object cannot be activated
     *
     * @param object $object
     * @param string $errorMessage
     */
    protected function onActivateError($object, $errorMessage)
    {
    }

    /**
     * Triggered after the object is deactivated
     *
     * @param object $object
     */
    protected function onDeactivateSuccess($object)
    {
    }

    /**
     * Triggered if the object cannot be deactivated
     *
     * @param object $object
     * @param string $errorMessage
     */
    protected function onDeactivateError($object, $errorMessage)
    {
    }

    /**
     * Triggered after the object is duplicated
     *
     * @param object $object The duplicated object
     * @param object $clone  The duplicate of the object
     */
    protected function onDuplicateSuccess($object, $clone)
    {
    }

    /**
     * Triggered if the object cannot be duplicated
     *
     * @param object $object
     * @param string $errorMessage
     */
    protected function onDuplicateError($object, $errorMessage)
    {
    }
}
