<?php
namespace project5\Auth;

class Anonymous implements ICaller
{
    const ROLE = 'anon';
    const CAN_LOGIN = 'login';

    /**
     * @return string
     */
    public function getAuthToken()
    {
        return 'anon';
    }

    /**
     * @param IArea $area
     * @return array
     */
    public function getAreaRoles(IArea $area)
    {
        return [self::ROLE];
    }

    /**
     * @param Manager $auth
     * @param IArea $area
     * @return void
     */
    public function loadPermissions(Manager $auth, IArea $area)
    {
        $auth->allow(self::ROLE, $area, self::CAN_LOGIN);
    }

    public function __toString()
    {
        return $this->getAuthToken();
    }
}