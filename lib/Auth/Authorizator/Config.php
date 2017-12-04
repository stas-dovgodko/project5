<?php
namespace project5\Auth\Authorizator;

use project5\Auth\IAuthorizator;
use project5\Auth\ICaller;
use project5\Auth\Token;
use project5\DI\Container;
use project5\DI\IContainer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class Config implements IAuthorizator, IContainer
{
    protected $list;
    protected $role;

    public function __construct(Config $parent = null, $role = null)
    {
        if ($parent === null) {
            $this->list = [];
        } else {
            $this->list = &$parent->list;
        }
        $this->role = $role;
    }

    public function addCaller($username, $md5, ICaller $caller = null)
    {
        if ($caller === null) {
            $roles = [];
            if ($this->role) $roles[] = $this->role;

            $caller = new Token(count($this->list), $roles);
        }

        $this->list[$username] = [
            'caller' => $caller,
            'username' => $username,
            'hash' => $md5,
            'token' => md5($caller->getAuthToken()),
        ];
    }

    /**
     * @param $username string
     * @param $password string
     * @return ICaller|null
     */
    public function authByCredentials($username, $password)
    {
        if (array_key_exists($username, $this->list)) {
            $info = $this->list['username'];

            if ($info['hash'] === md5($password)) {
                return $info['caller'];
            }
        }

        return null;
    }

    /**
     * @param $token string
     * @return ICaller|null
     */
    public function authByToken($token)
    {
        foreach($this->list as $info) {

            if (
                ($info['token'] === md5($token))
            ) {
                return $info['caller'];
            }
        }

        return null;
    }

    public static function Setup(Container $container, $id)
    {
        // TODO: Implement Setup() method.
    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        foreach($config as $username => $data) {
            if (is_string($data)) {
                $password = $data; $roles = [];
            } elseif (is_array($data) && count($data) === 2) {
                list($password, $roles) = $data;
            } else {
                continue; // @todo error
            }

            $caller = !empty($roles) ? new Token(md5($username), $roles) : null;
            $definition->addMethodCall('addCaller', [$username, $password, $caller]);
        }
    }
}