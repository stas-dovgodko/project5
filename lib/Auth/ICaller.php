<?php
namespace project5\Auth;

interface ICaller
{

    /**
     * @return string
     */
    public function getAuthToken();

    /**
     * @param IArea $area
     * @return array
     */
    public function getAreaRoles(IArea $area);

    /**
     * @param Manager $auth
     * @param IArea $area
     * @return void
     */
    public function loadPermissions(Manager $auth, IArea $area);
}