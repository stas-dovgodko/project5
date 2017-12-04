<?php
namespace project5\Session\Storage;

use project5\Session\IStorage;

class Native implements IStorage
{

    /**
     * Starts the session.
     *
     * @throws \RuntimeException If something goes wrong starting the session.
     * @param $id
     * @return bool True if started.
     */
    public function start($id)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_write_close();
        }
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            session_id($id);
            session_start();
        }
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
        if (session_id() === $id) {
            session_write_close();
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
        if (isset($_SESSION[$key])) {
            $existed = $_SESSION[$key];
            unset($_SESSION[$key]);
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
        $_SESSION[$key] = $data;
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
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
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
        return (isset($_SESSION[$key]));
    }
}