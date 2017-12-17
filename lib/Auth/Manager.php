<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 03.01.15
 * Time: 16:04
 */
namespace project5\Auth;


use project5\DI\IContainer;
use project5\DI\Container as DiContainer;

use project5\Web\Request;
use project5\Web\Response;
use Psr\Log\LoggerAwareInterface;
use project5\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use BeatSwitch\Lock\Manager as LockManager;
use BeatSwitch\Lock\Callers\SimpleCaller;
use Symfony\Component\DependencyInjection\Reference;

use project5\Template\Templater\Twig\IExtension;
use Twig_Environment;

class Manager implements IContainer, LoggerAwareInterface, IExtension
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    private $secureSalt = '!@sacenw3crwdsnw34asasdassd54zxcaw_23qad';

    const SESSION_NAMESPACE = 'auth';
    const SESSION_ACCOUNT_TOKEN = 'token';
    const COOKIE_BASE = 'auth';

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var IAuthorizator
     */
    protected $authorizator;

    /**
     * @var ICaller
     */
    protected $anonymousProto;

    /**
     * @var IArea
     */
    protected $area;

    public function __construct(IAuthorizator $authorizator, IArea $mainArea, LockManager $manager)
    {
        $this->manager = $manager;
        $this->authorizator = $authorizator;
        $this->area = $mainArea;

        $this->anonymousProto = new Anonymous();
    }

    /**
     * @return ICaller
     */
    public function getAnonymousProto()
    {
        return $this->anonymousProto;
    }

    /**
     * @param ICaller $anonymousProto
     */
    public function setAnonymousProto(ICaller $anonymousProto)
    {
        $this->anonymousProto = $anonymousProto;
    }



    public function getArea()
    {
        return $this->area;
    }

    public function setArea(IArea $area)
    {
        $this->area = $area;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public static function Setup(DiContainer $container, $id)
    {

    }

    public function allow($role, IArea $area = null, $action = null)
    {
        if ($area === null) $area = $this->area;

        $role_object = $this->manager->role($role);


        $this->log(sprintf('Allow %s?%s for %s', $area->getUid(), $action ? $action : '#all', $role));

        $role_object->allow($action ? $action : 'all', get_class($area), $area->getUid());

    }

    public function deny($role, IArea $area = null, $action = null)
    {
        if ($area === null) $area = $this->area;

        $role_object = $this->manager->role($role);

        $this->log(sprintf('Deny %s?%s for %s', $area->getUid(), $action ? $action : '#all', $role));

        $role_object->deny($action ? $action : 'all', get_class($area), $area->getUid());
    }

    public function allowAll($role, IArea $area = null)
    {
        if ($area === null) $area = $this->area;

        $role_object = $this->manager->role($role);



        $this->log(sprintf('Allow all at %s for %s', $area->getUid(), $role));

        $role_object->allow('all', get_class($area),$area->getUid());
    }

    public function denyAll($role, IArea $area = null)
    {
        if ($area === null) $area = $this->area;
        $role_object = $this->manager->role($role);



        $this->log(sprintf('Deny all at %s for %s', $area->getUid(), $role));

        $role_object->deny('all', get_class($area), $area->getUid());
    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        foreach ($config as $role_name => $info) {

            if (array_key_exists('all', $info)) {
                $all = $info['all'];
                if ($all === 'allow') {
                    $definition->addMethodCall('allowAll', [$role_name]);
                } elseif ($all === 'deny') {
                    $definition->addMethodCall('denyAll', [$role_name]);
                }
            }

            if (array_key_exists('allow', $info)) {
                $allow = $info['allow'];

                if (is_array($allow)) {
                    foreach ($allow as $action) {
                        $rules = [];
                        if (is_array($action)) {
                            foreach ($action as $action_key => $area) {
                                if (is_array($area)) {
                                    foreach($area as $area_item) {
                                        $definition->addMethodCall('allow', [$role_name, $area_item, $action_key]);
                                    }
                                } else {
                                    $definition->addMethodCall('allow', [$role_name, $area, $action_key]);
                                }
                            }
                        } else {
                            $definition->addMethodCall('allow', [$role_name, null, $action]);
                        }
                    }
                } elseif ($allow === 'all') {
                    $definition->addMethodCall('allowAll', [$role_name]);
                } elseif ($allow instanceof Reference) {
                    $definition->addMethodCall('allowAll', [$role_name, $deny]);
                }
            }
            if (array_key_exists('deny', $info)) {
                $deny = $info['deny'];

                if (is_array($deny)) {
                    foreach ($deny as $action) {
                        $rules = [];
                        if (is_array($action)) {
                            foreach ($action as $action_key => $area) {
                                if (is_array($area)) {
                                    foreach($area as $area_item) {
                                        $definition->addMethodCall('deny', [$role_name, $area_item, $action_key]);
                                    }
                                } else {
                                    $definition->addMethodCall('deny', [$role_name, $area, $action_key]);
                                }
                            }
                        } else {
                            $definition->addMethodCall('deny', [$role_name, null, $action]);
                        }
                    }
                } elseif ($deny === 'all') {
                    $definition->addMethodCall('denyAll', [$role_name]);
                } elseif ($deny instanceof Reference) {
                    $definition->addMethodCall('denyAll', [$role_name, $deny]);
                }
            }

            $role_definition = new Definition(Authorizator\Config::class);
            $role_definition->setDecoratedService('auth.authorizator.config');
            $role_definition->setPublic(false);
            $role_definition->setArguments([
                new Reference('auth.'.$role_name.'.inner'),
                $role_name
            ]);
            $builder->setDefinition('auth.'.$role_name, $role_definition);
        }
    }

    public function checkIfCan(ICaller $caller, $action, IArea $area = null)
    {
        if ($area === null) {
            $area = $this->area;
        }

        $resource = $area->getUid();
        $roles = (array)$caller->getAreaRoles($area);
        $this->log('area '.$resource.' roles: '.implode(', ', $roles));

        $caller->loadPermissions($this, $area);

        $lock = $this->manager->caller(new SimpleCaller(get_class($caller), $caller->getAuthToken(), $roles));

        $check = $lock->can($action, get_class($area), $area->getUid());

        $signature = $resource.'?'.$action .' = '.($check ? 'Y' : 'N');

        $this->log($signature);

        return $check;
    }

    /**
     * @param ICaller $caller
     * @param $action
     * @param null $resource
     * @throws Exception\ForbiddenAction Throws if action not allowed for caller
     */
    public function throwIfCant(ICaller $caller, $action, IArea $area = null)
    {
        if (!$this->checkIfCan($caller, $action, $area)) {

            if ($area === null) {
                $area = $this->area;
            }

            throw new Exception\ForbiddenAction('Not allowed #"'.$area->getUid().'" area for caller with ['.implode(',',$caller->getAreaRoles($area)).'] roles');
        }
    }

    protected function areaName($token)
    {
        return sprintf('%s_%s', $this->area->getUid(),$token);
    }

    /**
     * @param Request $request
     * @param IAuthorizator|null $authorizator
     * @return ICaller
     * @throws Exception\WrongCallerToken
     */
    public function load(Request $request, IAuthorizator $authorizator = null)
    {
        $session = $request->getSession();

        if ($authorizator === null) {
            $authorizator = $this->authorizator;
        }



        $caller = null;
        if ($account_token = $session->get($this->areaName(self::SESSION_NAMESPACE), self::SESSION_ACCOUNT_TOKEN)) {

            $this->log(sprintf('Try auth with %s Authorizator by session', get_class($authorizator)));

            $token = $this->decodeToken($account_token);

            $caller = $authorizator->authByToken($this->decodeToken($account_token));
        }

        if (!$caller) {
            if ($account_token = $request->getCookie($this->areaName(self::COOKIE_BASE), null)) {

                $this->log(sprintf('Try auth with %s Authorizator by cookies', get_class($authorizator)));

                $caller = $authorizator->authByToken($this->decodeToken($account_token));
            }
        }

        return $caller ?: clone $this->getAnonymousProto();
    }

    public function save(Response $response, ICaller $caller, $persistent = true)
    {
        $session = $response->getSession();
        $session->set($this->areaName(self::SESSION_NAMESPACE), self::SESSION_ACCOUNT_TOKEN, $this->encodeToken($caller->getAuthToken()));

        if ($persistent) {
            $response->setCookie($this->areaName(self::COOKIE_BASE), $this->encodeToken($caller->getAuthToken()), time()+60*60*24*30, $response->getRequest()->getBaseUrl()->getPath());
        }

        return $this;
    }

    public function reset(Response $response)
    {
        $session = $response->getSession();
        $session->set($this->areaName(self::SESSION_NAMESPACE), self::SESSION_ACCOUNT_TOKEN, null);

        $response->setCookie($this->areaName(self::COOKIE_BASE), null, time()+60*60*24*30, $response->getRequest()->getBaseUrl()->getPath());

        return $this;
    }

    public function encodeToken($token)
    {
        return base64_encode($this->secureSalt . json_encode($token));
    }

    public function decodeToken($secure)
    {
        $secured = base64_decode($secure);
        if (substr($secured, 0, strlen($this->secureSalt)) === $this->secureSalt) {
            return json_decode(substr($secured, strlen($this->secureSalt)), true);
        } else {
            throw new Exception\WrongCallerToken('Illegal secure token');
        }
    }

    /**
     * Get secured salt
     *
     * @return string
     */
    public function getSecureSalt()
    {
        return $this->secureSalt;
    }




    /**
     * @return IAuthorizator
     */
    public function getAuthorizator()
    {
        return $this->authorizator;
    }




    public function extendTwig(Twig_Environment $twig, array $options = [])
    {
        $twig->addTest(new \Twig_SimpleTest('can', function (ICaller $caller = null, $action, IArea $area = null) {

            if ($caller == null) {
                return false;
            } elseif ($caller instanceof ICaller) {
                return $this->checkIfCan($caller, $action, $area);
            } else {
                throw new \LogicException('Can test supports ICaller instances only');
            }
        }));
    }
}