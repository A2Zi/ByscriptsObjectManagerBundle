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

/**
 * Class AbstractManager
 *
 * @author Thierry Goettelmann <thierry@byscripts.info>
 */
abstract class AbstractObjectManager
{
    /**
     * Execute an action
     *
     * @param string $action
     * @param mixed  ...$data One or more arguments to pass to the process method
     *
     * @throws \Exception
     * @return bool
     */
    public function execute($action, $data)
    {
        $data = array_slice(func_get_args(), 1);

        try {
            $data[] = $this->callProcessMethod($action, $data);
            $this->callSuccessMethod(
                $action,
                $data
            );

            return true;
        } catch (ObjectManagerException $exception) {
            $data[] = $exception->getMessage();
            $this->callErrorMethod(
                $action,
                $data
            );

            return false;
        }
    }

    /**
     * Call a method dynamically
     * (seems ugly, but performance is around 50% better than a simple CUFA.
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function callMethod($method, array $arguments)
    {
        switch(count($arguments)) {
            case 0: return $this->$method();
            case 1: return $this->$method($arguments[0]);
            case 2: return $this->$method($arguments[0], $arguments[1]);
            case 3: return $this->$method($arguments[0], $arguments[1], $arguments[2]);
            case 4: return $this->$method($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
            default: return call_user_func_array(array($this, $method), $arguments);
        }
    }

    protected function callProcessMethod($action, array $data)
    {
        $name = 'process' . ucfirst($action);

        if (!method_exists($this, $name)) {
            throw new \Exception(sprintf('Method not found: %s in class %s', $name, get_class()));
        }

        return call_user_func_array(array($this, $name), $data);
    }

    protected function callSuccessMethod($action, array $data)
    {
        $name = 'on' . ucfirst($action) . 'Success';

        if (method_exists($this, $name)) {
            call_user_func_array(array($this, $name), $data);
        }
    }

    protected function callErrorMethod($action, array $data)
    {
        $name = 'on' . ucfirst($action) . 'Error';

        if (method_exists($this, $name)) {
            call_user_func_array(array($this, $name), $data);
        }
    }
}
