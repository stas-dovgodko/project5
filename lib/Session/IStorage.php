<?php
namespace project5\Session;

interface IStorage
{
    /**
     * Starts the session.
     *
     * @throws \RuntimeException If something goes wrong starting the session.
     * @param $id
     * @return bool True if started.
     */
    public function start($id);

    /**
     * Save the session to storage.
     *
     * @throws \RuntimeException If something goes wrong starting the session.
     * @param $id
     * @return bool
     */
    public function save($id);

    /**
     * Remove session property
     *
     * @param $key
     * @return mixed Old value if found
     */
    public function remove($key);

    /**
     * Set session property
     *
     * @param $key
     * @param $data
     * @return boolean
     */
    public function set($key, $data);

    /**
     * Get session property
     *
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * Check session property exists
     *
     * @param $key
     * @return mixed
     */
    public function has($key);
}