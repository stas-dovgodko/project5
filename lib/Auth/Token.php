<?php
namespace project5\Auth;



class Token implements ICaller
{
    private $_token;
    private $_roles;

    public function __construct($token, array $roles = [])
    {
        $this->_token = $token;
        $this->_roles = $roles;
    }

    /**
     * @return string
     */
    public function getAuthToken()
    {
        return $this->_token;
    }


    /**
     * @return array
     */
    public function getAreaRoles(IArea $area)
    {
        return $this->_roles;
    }
}