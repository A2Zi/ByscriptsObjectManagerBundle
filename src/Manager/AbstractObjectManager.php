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
     * @param array  $options
     *
     * @return bool
     */
    public function save($object, array $options = array())
    {
        $isNew = !(bool)$object->getId();

        try {
            $this->processSave($object, $options);

            $isNew
                ? $this->onCreateSuccess($object, $options)
                : $this->onUpdateSuccess($object, $options);

            return true;
        } catch (ObjectManagerException $exception) {
            $isNew
                ? $this->onCreateError($object, $exception->getMessage(), $options)
                : $this->onUpdateError($object, $exception->getMessage(), $options);

            return false;
        }
    }

    /**
     * Delete the object from the database
     *
     * @param object $object
     * @param array  $options
     *
     * @return bool
     */
    public function delete($object, array $options = array())
    {
        try {
            $this->processDelete($object, $options);
            $this->onDeleteSuccess($object, $options);

            return true;
        } catch (ObjectManagerException $exception) {
            $this->onDeleteError($object, $exception->getMessage(), $options);

            return false;
        }
    }

    /**
     * Duplicate the object
     *
     * @param object $object The object to duplicate
     * @param array  $options
     *
     * @return object|false The new object
     */
    public function duplicate($object, array $options = array())
    {
        try {
            $clone = $this->processDuplicate($object, $options);
            $this->onDuplicateSuccess($object, $clone, $options);

            return $clone;
        } catch (ObjectManagerException $exception) {
            $this->onDuplicateError($object, $exception->getMessage(), $options);

            return false;
        }
    }

    /**
     * Activate the object
     *
     * @param object $object The object to activate
     * @param array  $options
     *
     * @return bool
     */
    public function activate($object, array $options = array())
    {
        try {
            $this->processActivate($object, $options);
            $this->onActivateSuccess($object, $options);

            return true;
        } catch (ObjectManagerException $exception) {
            $this->onActivateError($object, $exception->getMessage(), $options);

            return false;
        }
    }

    /**
     * Deactivate the object
     *
     * @param object $object The object to deactivate
     * @param array  $options
     *
     * @return bool
     */
    public function deactivate($object, array $options = array())
    {
        try {
            $this->processDeactivate($object, $options);
            $this->onDeactivateSuccess($object, $options);

            return true;
        } catch (ObjectManagerException $exception) {
            $this->onDeactivateError($object, $exception->getMessage(), $options);

            return false;
        }
    }

    /**
     * @param object $object
     * @param array  $options
     */
    protected function processSave($object, array $options)
    {
        $this->persist($object)->flush();
    }

    /**
     * @param       $object
     * @param array $options
     */
    protected function processDelete($object, array $options)
    {
        $this->remove($object)->flush();
    }

    /**
     * @param       $object
     * @param array $options
     *
     * @return mixed
     */
    protected function processDuplicate($object, array $options)
    {
        $clone = clone $object;
        $this->persist($clone)->flush();

        return $clone;
    }

    /**
     * @param object $object
     * @param array  $options
     */
    protected function processActivate($object, array $options)
    {
        $object->setActive(true);
        $this->persist($object)->flush();
    }

    /**
     * @param object $object
     * @param array  $options
     */
    protected function processDeactivate($object, array $options)
    {
        $object->setActivate(false);
        $this->persist($object)->flush();
    }

    /**
     * Triggered after the object is created
     *
     * @param object $object
     * @param array  $options
     */
    protected function onCreateSuccess($object, array $options)
    {
    }

    /**
     * Triggered if the object can not be created
     *
     * @param object $object
     * @param string $errorMessage
     * @param array  $options
     */
    protected function onCreateError($object, $errorMessage, array $options)
    {
    }

    /**
     * Triggered after the object is updated
     *
     * @param object $object
     * @param array  $options
     */
    protected function onUpdateSuccess($object, array $options)
    {
    }

    /**
     * Triggered if the object can not be saved
     *
     * @param object $object
     * @param string $errorMessage
     * @param array  $options
     */
    protected function onUpdateError($object, $errorMessage, array $options)
    {
    }

    /**
     * Triggered after the object is deleted
     *
     * @param object $object
     * @param array  $options
     */
    protected function onDeleteSuccess($object, array $options)
    {
    }

    /**
     * Triggered after the object can not be deleted
     *
     * @param object $object
     * @param string $errorMessage
     * @param array  $options
     */
    protected function onDeleteError($object, $errorMessage, array $options)
    {
    }

    /**
     * Triggered after the object is activated
     *
     * @param object $object
     * @param array  $options
     */
    protected function onActivateSuccess($object, array $options)
    {
    }

    /**
     * Triggered if the object cannot be activated
     *
     * @param object $object
     * @param string $errorMessage
     * @param array  $options
     */
    protected function onActivateError($object, $errorMessage, array $options)
    {
    }

    /**
     * Triggered after the object is deactivated
     *
     * @param object $object
     * @param array  $options
     */
    protected function onDeactivateSuccess($object, array $options)
    {
    }

    /**
     * Triggered if the object cannot be deactivated
     *
     * @param object $object
     * @param string $errorMessage
     * @param array  $options
     */
    protected function onDeactivateError($object, $errorMessage, array $options)
    {
    }

    /**
     * Triggered after the object is duplicated
     *
     * @param object $object The duplicated object
     * @param object $clone  The duplicate of the object
     * @param array  $options
     */
    protected function onDuplicateSuccess($object, $clone, array $options)
    {
    }

    /**
     * Triggered if the object cannot be duplicated
     *
     * @param object $object
     * @param string $errorMessage
     * @param array  $options
     */
    protected function onDuplicateError($object, $errorMessage, array $options)
    {
    }
}
