<?php
namespace project5\Session\Storage;

use project5\Session\IStorage;

class ArrayMap implements IStorage
{
    /**
     * Session ID
     *
     * @var string
     */
    private $id;

    /**
     * Read callback
     *
     * @var callable
     */
    private $read;

    /**
     * Write callback
     *
     * @var callable
     */
    private $write;

    /**
     * Data storage
     *
     * @var array
     */
    private $storage = [];

    public function __construct(callable $read, callable $write)
    {
        $this->read = $read;
        $this->write = $write;
    }


    /**
     * Starts the session.
     *
     * @throws \RuntimeException If something goes wrong starting the session.
     * @param $id
     * @return bool True if started.
     */
    public function start($id)
    {
        $this->id = $id;

        $this->storage = call_user_func($this->read, $this->id);
    }

    /**
     * Save the session to storage.
     *
     * @throws \RuntimeException If something goes wrong starting the session.
     * @param $id
     * @return bool
     */
    public function save($id)
    {
        if ($this->id === $id) {
            call_user_func($this->write, $this->id, $this->storage);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove session property
     *
     * @param $key
     * @return mixed|null Old value if found
     */
    public function remove($key)
    {
        if (isset($this->storage[$key])) {
            $existed = $this->storage[$key];
            unset($this->storage[$key]);
            return $existed;
        }
        return null;
    }

    /**
     * Set session property
     *
     * @param $key
     * @param $data
     * @return boolean
     */
    public function set($key, $data)
    {
        $this->storage[$key] = $data;
        return true;
    }

    /**
     * Get session property
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->storage[$key])) {
            return $this->storage[$key];
        } else {
            throw new \OutOfBoundsException('Can\'t get "' . var_export($key, true) . '" session key');
        }
    }

    /**
     * Check session property exists
     *
     * @param $key
     * @return mixed
     */
    public function has($key)
    {
        return (isset($this->storage[$key]));
    }
}