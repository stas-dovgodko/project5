<?php
namespace project5\Ldap;

use \Exception;

interface IDriver
{


    /**
     * LDAP server connection
     *
     * In the constructor we initiate a connection with the specified LDAP server
     * and optionally allow the setup of LDAP protocol version
     *
     * @access public
     * @param string $hostname Hostname of your LDAP server
     * @param int $port Port of your LDAP server
     * @param int $protocol (optional) Protocol version of your LDAP server
     */
    public function __construct($hostname, $port, $protocol = null);

    /**
     * @param string $dn
     * @return self
     */
    public function setDn($dn);

    /**
     * @return string
     */
    public function getDn();

    /**
     * Bind as an administrator in the LDAP server
     *
     * Bind as an administrator in order to execute admin-only tasks,
     * such as add, modify and delete users from the directory.
     *
     * @param null $rdn
     * @param null $password
     * @return $this
     */
    public function bind($rdn = null, $password = null);

    /**
     * Get records based on a query
     *
     * Returns information from users within the directory that match a certain query
     *
     * @throws Exception
     * @param string $filter The search filter used to query the directory. For more info, see: http://www.mozilla.org/directory/csdk-docs/filter.htm
     * @param array $attributes (optional) An array containing all the attributes you want to request
     * @return \Traversable Returns the records
     */
    public function filter($filter, $attributes = null);

    /**
     * Inserts a new user in LDAP
     *
     * This method will take an array of information and create a new entry in the
     * LDAP directory using that information.
     *
     * @throws Exception
     * @param string $uid Username that will be created
     * @param array $data Array of user information to be inserted
     * @return bool Returns true on success and false on error
     */
    public function add($uid, $data);

    /**
     * Removes an existing user in LDAP
     *
     * This method will remove an existing user from the LDAP directory
     *
     * @throws Exception
     * @param string $uid Username that will be removed
     * @return bool Returns true on success and false on error
     */
    public function delete($uid);

    /**
     * Modifies an existing user in LDAP
     *
     * This method will take an array of information and modify an existing entry
     * in the LDAP directory using that information.
     *
     * @throws Exception
     * @param string $uid Username that will be modified
     * @param array $data Array of user information to be modified
     * @return bool Returns true on success and false on error
     */
    public function modify($uid, $data);

    /**
     * Close the LDAP connection
     *
     */
    public function close();
}