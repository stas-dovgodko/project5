<?php
namespace project5\Auth\Filter;

use project5\Auth\IArea;
use project5\Auth\IAuthorizator;
use project5\Auth\Manager;
use project5\Web\Exception\NotAllowedException;
use project5\Web\Filter;
use project5\Web\Filter\Chain;
use project5\Web\Request;
use project5\Web\Response;

class Auth extends Filter {
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var IArea
     */
    protected $area;

    protected $resource;
    protected $action;

    public function __construct(Manager $manager, IArea $area)
    {
        $this->manager = $manager;
        $this->area = $area;
    }


    /**
     * Pre filter method
     *
     * For process the rest of filter chain this code should be call inside:
     * <code>
     * $filterChain->doPreFilter ();
     * </code>
     *
     * @throws NotAllowedException
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @return Response|void
     */
    protected function preFilter(Request $request, Response $response, Chain $filterChain)
    {
        $caller = $this->manager->load($request);

        $action = $filterChain->getOption('action');
        $area = $filterChain->getOption('area', $this->area);

        $caller->loadPermissions($this->manager, $area);


        if ($action && !$this->manager->checkIfCan($caller, $action, $area)) {
            throw new NotAllowedException('Not allowed for this person');
        }

        $filterChain->setArgument('auth_caller', $caller);
        $filterChain->getRoute()->addProperty('auth_caller', $caller);


        $this->log('Auth with caller: ' . var_export($caller, true));
    }
}