<?php


namespace Byscripts\Bundle\ObjectManagerBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\LockMode;

abstract class AbstractDoctrineObjectManager extends AbstractObjectManager
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
        $arguments   = func_get_args();
        $arguments[] = !(bool)$object->getId();

        return $this->callMethod('execute', $arguments);
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
        return $this->callMethod('execute', func_get_args());
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
        return $this->callMethod('execute', func_get_args());
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
        return $this->callMethod('activate', func_get_args());
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
        return $this->callMethod('deactivate', func_get_args());
    }

    /**
     * @param object $object
     * @param bool   $isNew
     */
    protected function processSave($object, $isNew)
    {
        $this->persist($object)->flush();
    }

    /**
     * @param object $object
     * @param bool   $isNew
     */
    protected function onSaveSuccess($object, $isNew)
    {
    }

    /**
     * @param object $object
     * @param bool   $isNew
     * @param string $errorMessage
     */
    protected function onSaveError($object, $isNew, $errorMessage)
    {
    }

    /**
     * @param object $object
     */
    protected function processDelete($object)
    {
        $this->remove($object)->flush();
    }

    /**
     * @param object $object
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
}