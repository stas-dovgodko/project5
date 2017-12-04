<?php
namespace project5\Ldap\Driver\Native;

use project5\Ldap\IDriver;
use \Exception;

class Native implements IDriver {
    /**
     * Holds the LDAP server connection
     *
     * @var resource
     */
    private $_ldap;

    /**
     * Holds the default Distinguished Name. Ex.: ou=users,dc=demo,dc=com
     *
     * @var string
     */
    private $_dn;

    private $_rdn;
    private $_pass;

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
    public function __construct($hostname, $port, $protocol = null) {
        $this->_ldap = ldap_connect($hostname, $port);

        if($protocol != null) {
            ldap_set_option($this->_ldap, LDAP_OPT_PROTOCOL_VERSION, $protocol);
        }
    }

    /**
     * @param string $dn
     * @return self
     */
    public function setDn($dn)
    {
        $this->_dn = $dn;

        return $this;
    }

    /**
     * @return string
     */
    public function getDn()
    {
        return $this->_dn;
    }



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
    public function bind($rdn = null, $password = null) {
        $this->_rdn = $rdn;
        $this->_pass = $password;

        return $this;
    }

    /**
     * Bind as an administrator in the LDAP server
     *
     * Bind as an administrator in order to execute admin-only tasks,
     * such as add, modify and delete users from the directory.
     *
     * @access private
     * @return bool Returns if the bind was successful or not
     */
    private function _bind() {
        $bind = ldap_bind($this->_ldap, $this->_rdn, $this->_pass);
        return $bind;
    }

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
    public function filter($filter, $attributes = null) {
        if($this->_bind()) {
            if($attributes !== null) {
                $search = ldap_search($this->_ldap, $this->_dn, $filter, $attributes);
            } else {
                $search = ldap_search($this->_ldap, $this->_dn, $filter);
            }

            if(!$search) {
                $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
                throw new Exception($error);
            }
            $data = ldap_get_entries($this->_ldap, $search);


            for ($i=0; $i < $data["count"]; $i++) {
                $idx = null;
                $record = $this->_populate($data[$i], $idx);
                if ($idx) {
                    yield $idx => $record;
                } else {
                    yield $record;

                }
            }
        } else {
            $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
            throw new Exception($error);
        }
    }

    private function _populate($record, &$index)
    {
        $row = [];
        if (isset($record['dn'])) {
            $index = (string)$record['dn'];
            $row['dn'] = $index;
        }

        for ( $i = 0; $i < $record['count']; $i++ ) {
            $attribute = strtolower($record[$i]);
            if ( $record[$attribute]['count'] == 1 ) {
                $row[$attribute] = $record[$attribute][0];
            } else {
                $v = array();
                for ( $j = 0; $j < $record[$attribute]['count']; $j++ ) {
                    $v[] = $record[$attribute][$j];
                }

                $row[$attribute]= $v;
            }
        }

        return $row;
    }

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
    public function add($uid, $data) {
        if($this->_bind()) {
            $add = ldap_add($this->_ldap, "uid=$uid," . $this->_dn, $data);
            if(!$add) {
                $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
                throw new Exception($error);
            } else {
                return true;
            }
        } else {
            $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
            throw new Exception($error);
        }
    }

    /**
     * Removes an existing user in LDAP
     *
     * This method will remove an existing user from the LDAP directory
     *
     * @throws Exception
     * @param string $uid Username that will be removed
     * @return bool Returns true on success and false on error
     */
    public function delete($uid) {
        if($this->_bind()) {
            $delete = ldap_delete($this->_ldap, "uid=$uid," . $this->_dn);
            if(!$delete) {
                $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
                throw new Exception($error);
            } else {
                return true;
            }
        } else {
            $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
            throw new Exception($error);
        }
    }

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
    public function modify($uid, $data) {
        if($this->_bind()) {
            $modify = ldap_modify($this->_ldap, "uid=$uid," . $this->_dn, $data);
            if(!$modify) {
                $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
                throw new Exception($error);
            } else {
                return true;
            }
        } else {
            $error = ldap_errno($this->_ldap) . ": " . ldap_error($this->_ldap);
            throw new Exception($error);
        }
    }

    /**
     * Close the LDAP connection
     *
     * @access public
     */
    public function close() {
        ldap_close($this->_ldap);
    }
}