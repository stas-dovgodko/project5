<?php
namespace project5\Auth;

interface IAuthorizator
{
    /**
     * @param $username string
     * @param $password string
     * @return ICaller|null
     */
    public function authByCredentials($username, $password);

    /**
     * @param $token string
     * @return ICaller|null
     */
    public function authByToken($token);
}