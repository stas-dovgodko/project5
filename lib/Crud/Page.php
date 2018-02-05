<?php
    namespace project5\Crud;


    use project5\Crud\Decorator\Crud;
    use project5\Crud\Decorator\Number;
    use project5\Crud\Decorator\Switcher;
    use project5\DI\Container;
    use project5\Form;

    use project5\Provider\Compare\OrComposition;
    use project5\Provider\Compare\Range;
    use project5\Provider\Field\Boolean;
    use project5\Provider\Field\Relation;
    use project5\Provider\ICanRank;
    use project5\Template;
    use project5\Upload\Handler;
    use project5\Web\Notifications;
    use project5\Web\ResponseDecorator\JSON;
    use project5\Web\ResponseDecorator\HTML;


    use project5\Provider\ICanBeSorted;
    use project5\Provider\Sort\ISorter;
    use project5\Provider\Sort\Asc;
    use project5\Provider\Sort\Desc;


    use project5\IProvider;
    use project5\Provider\IEntity;
    use project5\Provider\IField;
    use project5\Provider\Field\Number as NumberField;
    use project5\Provider\ICanBePaged;
    use project5\Provider\ICanBeCounted;
    use project5\Provider\ICanDelete;
    use project5\Provider\ICanBeFiltered;
    use project5\Provider\ICanSearch;
    use project5\Provider\ICanSave;


    use project5\Provider\Compare\Equal;
    use project5\Web\ControllerManager;
    use project5\Web\Group;

    use project5\Web\Request;
    use project5\Web\Response;
    use project5\Web\Route;

    /**
     * Class Page
     *
     * @method hasList()
     * @method hasView()
     * @method hasRel()
     * @method hasSorting()
     * @method urlList()
     * @method urlEdit()
     * @method urlAdd()
     * @method urlDelete()
     * @method urlView()
     *
     * @package CRUD
     */
    class Page extends Group
    {
        protected $title;

        protected $actions = [];

        protected $titles = [];

        protected $default_sort = '';

        /**
         * @var Route
         */
        private $routeList, $routeAdd, $routeEdit, $routeView, $routeDelete, $routeRel, $routeRank;

        protected $routeMdelete, $routeMview, $routeRellist;


        const SERVICE_LIST = 'list';
        const SERVICE_EDIT = 'edit';
        const SERVICE_ADD = 'add';
        const SERVICE_DELETE = 'delete';
        const SERVICE_VIEW = 'view';
        const SERVICE_SEARCH = 'search';
        const SERVICE_FILTER = 'filter';
        const SERVICE_SORTING = 'sorting';
        const SERVICE_RANK = 'rank';
        const SERVICE_REL = 'rel';
        const SERVICE_EXTLIST = 'extlist';

        const SORTING_ASC = 'asc';
        const SORTING_DESC = 'desc';

        protected $services = [];

        protected $groups = [];

        /**
         * @var array
         */
        public $relations = [];

        protected $tabGroup = null;

        /**
         * @var HTML
         */
        protected $htmlDecorator;

        /**
         * @var JSON
         */
        protected $jsonDecorator;


        /**
         * @var IProvider
         */
        protected $provider;

        /**
         * @var Notifications
         */
        protected $notifications;

        protected $container;

        protected $search = null;

        protected $ns;

        /**
         * @var callable|null
         */
        protected $fieldsFilter = null;

        public function __construct(Container $container)
        {
            $this->container = $container;
            $this->ns = md5(self::CLASS . get_class($this).'v1');

            parent::__construct(function () use ($container) {

                $manager = function ($name) use ($container) {

                    $class = get_class($this);
                    $template_name = (($pos = strrpos($class, '\\')) !== false) ? substr($class, $pos+1) : $class;


                    $controller_manager = new ControllerManager($container, $this);//, ['html' => 'handle'.$name]);

                    if ($this->logger) {
                        $controller_manager->setLogger($this->logger);
                    }

                    $decorator = $this->getHtmlDecorator();
                    if ($decorator) {
                        $template_name .= DIRECTORY_SEPARATOR.$name;
                        $decorator = clone $decorator;
                        //$decorator->setTemplateName(['@crud/page/'.$template_name, 'Crud/'.$name]);
                        $decorator->setTemplateName(['' . $template_name, 'page/' . $name], 'crud');


                        $controller_manager->addMap('html', 'handle' . $name, $decorator);
                    }

                    return $controller_manager;
                };

                $json = function ($name) use ($container) {

                    $decorator = $this->getJsonDecorator();
                    $controller_manager = new ControllerManager($container, $this);
                    if ($this->logger) {
                        $controller_manager->setLogger($this->logger);
                    }

                    if ($decorator) {
                        $controller_manager->addMap('json', 'handle'.$name, clone $decorator);
                    }




                    return $controller_manager;
                };

                //$this->init();


                if ($this->hasList()) {
                    $this->routeList = $this->addRoute('', $manager('list'));
                }

                if ($this->hasAdd()) {
                    $this->routeAdd = $this->addRoute('add/', $manager('add'));
                }
                if ($this->hasDelete()) {
                    $this->routeDelete = $this->addRoute('delete/:id', $manager('delete'));
                    $this->routeMdelete = $this->addRoute('delete/', $manager('mdelete'));
                }

                if ($this->hasEdit()) $this->routeEdit = $this->addRoute('edit/:id', $manager('edit'));
                if ($this->hasView()) {
                    $this->routeView = $this->addRoute('view/:id', $manager('view'));
                    $this->routeMview = $this->addRoute('view/', $manager('mview'));
                }

                if ($this->hasRel()) {
                    $this->routeRel = $this->addRoute('rel/:key', $json('rel'));
                    $this->routeRellist = $this->addRoute('rel/', $json('rellist'));
                }

                if ($this->hasRank()) {
                    $this->routeRank = $this->addRoute('rank/:id/:action', $manager('rank'));
                }

            });
        }

        /**
         * @return callable|null
         */
        public function getFieldsFilter()
        {
            return $this->fieldsFilter;
        }

        /**
         * @param $pattern
         * @param $method
         * @return Route
         */
        protected function addHtmlHandler($pattern, $method, $name = null)
        {
            $container = $this->container;

            $manager = function ($name) use ($container) {

                $class = get_class($this);
                $template_name = (($pos = strrpos($class, '\\')) !== false) ? substr($class, $pos+1) : $class;

                $template_name .= DIRECTORY_SEPARATOR.$name;

                $decorator = clone $this->getHtmlDecorator();
                //$decorator->setTemplateName(['@crud/page/'.$template_name, 'Crud/'.$name]);
                $decorator->setTemplateName([''.$template_name, 'page/'.$name], 'crud');



                $controller_manager = new ControllerManager($container, $this);//, ['html' => 'handle'.$name]);
                $controller_manager->setLogger($this->logger);
                //$controller_manager->setResponseDecorator($decorator);
                $controller_manager->addMap('html', 'handle'.$name, $decorator);

                return $controller_manager;
            };

            return $this->addRoute($pattern, $manager($method), $method ?: $name);
        }

        protected function addJsonHandler($pattern, $method, $name = null)
        {
            $container = $this->container;

            $json = function ($name) use ($container) {

                $decorator = clone $this->getJsonDecorator();

                $controller_manager = new ControllerManager($container, $this);
                $controller_manager->setLogger($this->logger);
                $controller_manager->addMap('json', 'handle'.$name, $decorator);

                return $controller_manager;
            };

            return $this->addRoute($pattern, $json($method));
        }

        public function addAction($services, $name, $title, $icon = '')
        {
            if (!is_array($services)) $services = [$services];

            foreach ($services as $service) {

                if (array_key_exists($service, $this->actions)) {
                    $this->actions[$service] = [];
                }

                $class = get_class($this);
                $template_name = (($pos = strrpos($class, '\\')) !== false) ? substr($class, $pos+1) : $class;

                $template_name .= DIRECTORY_SEPARATOR.$name;

                $decorator = clone $this->getHtmlDecorator();
                $decorator->setTemplateName([''.$template_name, 'page/'.$name], 'crud');

                
                $controller_manager = new ControllerManager($this->container, $this);
                $controller_manager->setLogger($this->logger);
                $controller_manager->addMap('html', 'action'.$name, $decorator);

                $decorator = clone $this->getJsonDecorator();
                $controller_manager->addMap('json', 'action'.$name, $decorator);

                $route = $this->addRoute('action/'.$service.'/'.$name, $controller_manager);

                $this->actions[$service][$name] = [$name, $title, $icon, $route];
            }
        }

        public function getActions($service)
        {
            if (array_key_exists($service, $this->actions)) {
                return array_keys($this->actions[$service]);
            } else {
                return [];
            }
        }

        public function hasActions($service)
        {
            if (array_key_exists($service, $this->actions)) {
                return true;
            } else {
                return false;
            }
        }

        public function getActionTitle($service, $id)
        {
            if (array_key_exists($service, $this->actions)) {
                return isset($this->actions[$service][$id][1]) ? $this->actions[$service][$id][1] : null;
            } else {
                return null;
            }
        }

        public function getActionIcon($service, $id)
        {
            if (array_key_exists($service, $this->actions)) {
                return isset($this->actions[$service][$id][2]) ? $this->actions[$service][$id][2] : null;
            } else {
                return null;
            }
        }

        /**
         * @return Notifications
         */
        public function getNotifications()
        {
            return $this->notifications;
        }

        /**
         * @param Notifications $notifications
         */
        public function setNotifications(Notifications $notifications)
        {
            $this->notifications = $notifications;
        }

        public function getSearch()
        {
            return $this->search;
        }



        /**
         * @return IProvider
         */
        public function getProvider()
        {
            return $this->provider;
        }

        /**
         * @param IProvider $provider
         */
        public function setProvider(IProvider $provider)
        {
            $this->provider = $provider;
        }



        /**
         * @return HTML
         */
        public function getHtmlDecorator()
        {
            return $this->htmlDecorator;
        }

        /**
         * @param HTML $htmlDecorator
         */
        public function setHtmlDecorator(HTML $htmlDecorator)
        {
            $this->htmlDecorator = $htmlDecorator;
        }

        /**
         * @return JSON
         */
        public function getJsonDecorator()
        {
            return $this->jsonDecorator;
        }

        /**
         * @param JSON $jsonDecorator
         */
        public function setJsonDecorator(JSON $jsonDecorator)
        {
            $this->jsonDecorator = $jsonDecorator;
        }



        protected function addTab($name, $rules, $title = null)
        {
            if ($this->tabGroup === null) {
                $this->tabGroup = $name;
            }
            $this->groups[$name] = [$rules, $title ? $title : $name];

            return $this;
        }

        public function getTitle()
        {
            return $this->title;
        }

        protected function init() {

            // nothing todo
        }

        public function isEnabled($service)
        {
            $status = ((sizeof($this->services) == 0) || !isset($this->services[$service]) || $this->services[$service] === true);

            return $status;
        }

        public function __call($name, $arguments)
        {
            if (substr($name, 0, 3) === 'has') {
                $constant_name = get_called_class().'::SERVICE_'.strtoupper(substr($name,3));
                if (defined($constant_name)) {
                    return $this->isEnabled(constant($constant_name));
                }
            } elseif ((!isset($arguments[0]) || is_array($arguments[0])) && substr($name, 0, 3) === 'url') {

                $this->_initialize();

                $route_name = substr($name, 3);
                $property_name = 'route'.$route_name;

                if (!empty($this->$property_name)) {

                    $route = $this->$property_name;
                    $opts = isset($arguments[0]) ? $arguments[0]: [];

                    return $this->getRoot()->uri($route, $opts);
                } elseif ($this->hasRoute($route_name)) {
                    $route = $this->getRoute($route_name);
                    $opts = isset($arguments[0]) ? $arguments[0]: [];

                    return $this->getRoot()->uri($route, $opts);
                }
            }
        }

        /**
         * @param IProvider $provider
         * @param Request $request
         * @param Response $response
         * @return Form
         */
        protected function buildFilterForm(IProvider $provider, Request $request, Response $response)
        {
            if ($provider instanceof ICanBeFiltered) {
                //$filter_data = $request->persistParam('filter_data', array(), $this->ns);

                $params = [];
                if ($provider instanceof ICanBePaged) {
                    $params['offset'] = 0;
                }
                $url = $this->urlList($params);

                $filter_form = new Form($request, (string)$url, 'filter_data');

                $fields = $provider->getFilterable();
                foreach($fields as $field) /** @var $field IField */
                {
                    $decorator = $this->decorateField($field);
                    if ($decorator && !$decorator->isFlagEnabled(Decorator::FLAG_HIDE_FILTER)) {

                        $form_field = $decorator->getFormField();
                        $form_field->canBeEmpty(true); // al filters can be empty
                        $filter_form->append($form_field);
                        $filter_form->setValue($form_field->getName(), $request->persistParam($filter_form->getFieldName($form_field->getName()), null, $this->ns));
                    }
                }

                $filter_form->setCsrf($request);


                return $filter_form;
            }
        }

        /**
         * @param IProvider $provider
         * @param Request $request
         * @param Response $response
         * @return Form
         */
        protected function buildAddForm(Request $request, Response $response)
        {
            $form = new Form($request, $this->urlAdd(), 'add');

            $fields = $this->provider->getFields();
            try {
                foreach ($fields as $field) /** @var $field IField */ {
                    if (!$field->isSurrogate()) {
                        $decorator = $this->decorateField($field);
                        if ($decorator && !$decorator->isFlagEnabled(Decorator::FLAG_IMMUTABLE)) {
                            $form_field = $decorator->getFormField();

                            $form->append($form_field, new Validator\Field($field));

                            if ($this->tabGroup && isset($this->groups[$this->tabGroup])) {
                                list($fields, $title) = $this->groups[$this->tabGroup];

                                if (isset($fields[$field->getName()])) {
                                    $form->setDefault($form_field, $fields[$field->getName()]);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                throw $e;
            }

            $form->setCsrf($request);


            return $form;
        }

        protected function decodeUid($coded)
        {
            return @json_decode($coded);
        }

        public function encodeUid($var)
        {
            if ($var instanceof IEntity) {
                $uid = $var->getUid();
            } else {
                $uid = $var;
            }

            return json_encode($uid);
        }

        protected function prepareForm(Form $form)
        {

        }

        /**
         * @param IProvider $provider
         * @param Request $request
         * @param Response $response
         * @param IEntity $object
         * @return Form
         */
        protected function buildEditForm(Request $request, Response $response, IEntity $object)
        {
            $fields = $this->provider->getFields();

            $data = [];
            foreach($fields as $field) /** @var $field IField */
            {
                if (!$field->isSurrogate()) {
                    $data[$field->getName()] = $field->get($object);
                }
            }

            $pk_id = $this->encodeUid($object);
            $key = sprintf('edit_%s', base64_encode($pk_id));
            $request->persistParam($key, $data);

            $action = $this->urlEdit(['id' => $pk_id]);

            $form = new Form($request, $action, $key);


            foreach($fields as $field) /** @var $field IField */
            {
                if (!$field->isSurrogate()) {
                    $decorator = $this->decorateField($field);

                    if ($decorator) {

                        $form_field = $decorator->getFormField();
                        if ($decorator->isFlagEnabled(Decorator::FLAG_IMMUTABLE)) {
                            $form_field->setReadonly(true);
                        }

                        $form->append($form_field, new Validator\Field($field));
                        $form->setDefault($form_field->getName(), $field->get($object));

                    }
                }
            }

            $form->setCsrf($request);


            return $form;
        }



        public function handleList(Response $response, Request $request)
        {
            $provider = $this->provider;

            if (!$this->hasList()) {
                return ControllerManager::NOT_FOUND;
            }

            if ($provider instanceof ICanBePaged)
            {
                $default_limit = $provider->getPagerLimit(); if (!$default_limit) $default_limit = 10;
                $provider->setPagerLimit($request->persistParam('perpage', $default_limit, $this->ns));
                $provider->setPagerOffset($request->persistParam('offset', 0, $this->ns));
            }


            $sortable = [];
            if ($provider instanceof ICanBeSorted) {
                $sort = $request->persistParam('sort', $this->default_sort, $this->ns);
                if ($sort) {
                    if (substr($sort, 0, 5) === 'desc_') {
                        $sorter = new Desc(); $field_name = substr($sort, 5);
                    } else {
                        $sorter = new Asc(); $field_name = $sort;
                    }
                } else {
                    $field_name = null; $sorter = null;
                }
                if ($this->routeList) foreach($provider->getSortable() as $field)
                {
                    if (($field_name === $field->getName()) && $sorter instanceof ISorter) {
                        $provider = $provider->sort($field, $sorter);
                    }

                    $url = $this->getRoot()->uri($this->routeList, array('sort' => ($sorter instanceof Asc ? 'desc_':'').$field->getName()));
                    $sortable[$field->getName()] = [
                        'current' => ($field_name === $field->getName()) ? (($sorter instanceof Desc) ? 'desc_':'asc_') : null,
                        'url' => $url,
                    ];
                }
            }

            if ($provider instanceof ICanSearch) {
                $this->doSearch($provider, $this->search = $request->persistParam('search', null, $this->ns));
            }

            /*foreach($provider->iterate() as $item) {

            }*/

            $tab_urls = [];
            if (!empty($this->groups)) {
                foreach($this->groups as $name => $data) {
                    list($fields, $title) = $data;
                    $tab_urls[$name] = [$title, $this->urlList(['tabfilter' => $name])];
                }
            }

            return [
                'title' => 'List',
                'page' => $this,
                'sortable' => $sortable,
                'tab' => $this->tabGroup,
                'tabs' => $tab_urls
            ];
        }

        public function urlRel($ref_name, Request $request)
        {
            $this->_initialize();

            if ($this->routeRel) {
                $rand = strtoupper(md5(microtime(true).rand(0, PHP_INT_MAX-1)));
                $request->persistParam($rand, array($ref_name), $this->ns);

                return $this->getRoot()->uri($this->routeRel, ['key' => $rand]);
            }
        }


        public function urlAction($service, $key)
        {
            if (array_key_exists($service, $this->actions) && array_key_exists($key, $this->actions[$service])) {


                return $this->getRoot()->uri($this->actions[$service][$key][3], []);
            }
        }


        public function handleRel(Request $request, Response $response, $key)
        {
            try {
                if (list($ref_name) = $request->persistParam($key, null, $this->ns)) {

                    $field = null;
                    foreach ($this->provider->getFields() as $f) {
                        if ($f->getName() === $ref_name) {
                            $field = $f;
                        }
                    }


                    //die('?'.get_class($field).' '.get_class($this->provider));
                    if ($field && (($field instanceof \project5\Provider\Field\Relation) || ($field instanceof \project5\Provider\Field\NMRelation))) {
                        $form_field = new \project5\Crud\Relation($field);

                        return $form_field->getData($this,
                            $request->getParam('page', 1),
                            $request->getParam('page_limit', 10),
                            $request->getParam('q')
                        );


                    } else {
                        return $response->withStatus(Response::STATUS_NOT_FOUND);
                    }
                } else {
                    return $response->withStatus(Response::STATUS_FORBIDDEN);
                }
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        public function handleRellist(Request $request, IProvider $provider)
        {
            try {

                $dump = function (IEntity $item) {
                    return (string)$item;
                };

                $filter = null;
                if ($q = $request->getParam('q')) {
                    if ($provider instanceof ICanSearch) {
                        $provider->setSearchTerm($q);
                    }
                }

                $data = array('results' => array());

                if ($provider instanceof ICanBePaged) {
                    $page = $request->getParam('page', 1);
                    $per_page = $request->getParam('page_limit', 10);

                    $provider->setPagerOffset(($page - 1) * $per_page);
                    $provider->setPagerLimit($per_page);
                    $data['more'] = $provider->hasNextPage();
                }


                foreach ($provider->iterate() as $object) {
                    /** @var $object IEntity */
                    $data['results'][] = array('id' => $this->encodeUid($object), 'text' => (string)$dump($object));
                }

                if (($provider instanceof ICanBeCounted) && $provider->canBeCounted()) {
                    $data['total'] = $provider->getCount();

                }

                return $data;

            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        public function handleRank(Request $request, Response $response, $id, $action)
        {
            if ($this->hasRank()) {
                try {
                    $entity = $this->provider->findByUid($this->decodeUid($id));

                    switch ($action) {
                        case 'up':
                            $this->provider->moveUp($entity);
                            break;
                        case 'down':
                            $this->provider->moveDown($entity);
                            break;
                    }

                    return Response\Factory::Redirect($response, $this->urlList());
                } catch (\Exception $e) {
                    $this->notifications->addError($e->getMessage());
                }
            }
        }

        public function handleDelete(Request $request, Response $response, $id)
        {
            if (($this->provider instanceof ICanDelete) && $this->provider->canDelete()) {
                $id = $this->decodeUid($pk = $id);
                $has_confirm = $request->hasParam('confirm');

                if ($has_confirm) {

                    if ($request->getParam('confirm')) {
                        try {
                            if ($this->provider->deleteByUid($id)) {
                                $this->notifications->addMessage('Deleted');
                            }

                        } catch (\Exception $e) {
                            $this->notifications->addError($e->getMessage());
                        }
                    } else {
                        $this->notifications->addMessage('Canceled');
                    }
                    return Response\Factory::Redirect($response, $this->urlList());
                } else { // display confirm screen

                    return [
                        'title' => 'Delete',
                        'entity' => $this->provider->findByUid($id),
                        'page' => $this,
                        'provider' => $this->provider,
                    ];
                }

            }
        }

        public function handleMdelete(Request $request, Response $response)
        {
            if (($this->provider instanceof ICanDelete) && $this->provider->canDelete()) {
                $ids = $request->getParam('mpk', []);
                if (is_array($ids) && !empty($ids)) {
                    array_walk($ids, [$this, 'decodeUid']);

                    $entities = new \SplObjectStorage();
                    foreach($ids as $uid) {
                        if ($entity = $this->provider->findByUid($this->decodeUid($uid))) {
                            $entities->attach($entity);
                        }
                    }

                    if ($request->hasParam('confirm')) {

                        $deleted = 0;
                        foreach($entities as $entity) {
                            try {
                                if ($this->provider->deleteEntity($entity)) {
                                    $deleted++;
                                    $entities->detach($entity);
                                }
                            } catch (\Exception $e) {
                                $this->notifications->addError($e->getMessage());
                            }
                        }

                        if ($deleted > 0 && $entities->count() == 0) {
                            // all deleted
                            $this->notifications->addMessage('Successfully deleted - %s', [$deleted]);
                            return Response\Factory::Redirect($response, $this->urlList());
                        }
                    } elseif ($request->hasParam('cancel')) {
                        $this->notifications->addMessage('Canceled');
                        return Response\Factory::Redirect($response, $this->urlList());

                    } else { // display confirm screen

                        return [
                            'title' => 'Delete',
                            'entities' => $entities,
                            'page' => $this,
                        ];
                    }

                } else {
                    $this->notifications->addError('Empty set');
                    return Response\Factory::Redirect($response, $this->urlList());
                }
            }
        }

        public function handleAdd(Response $response, Request $request)
        {
            if ($this->provider instanceof ICanSave)
            {
                $object = $this->provider->createEntity();

                $form = $this->buildAddForm($request, $response);

                $this->prepareForm($form);

                if ($request->isPost() && $form->validate()) {

                    try {
                        foreach ($this->provider->getFields(function (IField $field) {
                            return !$field->isSurrogate();
                        }) as $field) /** @var $field IField */ {

                            if (!$field->isSurrogate()) {
                            $decorator = $this->decorateField($field);

                            if ($decorator && !$decorator->isFlagEnabled(Decorator::FLAG_IMMUTABLE)) {
                                $field_value = $form->getValue($decorator->getFormField()->getName());
                                $decorator->getField()->set($object, $field_value);
                            }}
                        }
                        if ($this->provider->saveEntity($object)) {
                            $this->notifications->addMessage('Saved');
                        }
                    } catch (\Exception $e) {
                        $this->notifications->addError($e->getMessage());
                    }

                    if ($request->hasParam('save_and_list')) {
                        return Response\Factory::Redirect($response, $this->urlList());
                    } elseif ($object && $this->hasEdit()) {
                        return Response\Factory::Redirect($response, $this->urlEdit(['id' => $this->encodeUid($object->getUid())]));
                    } else {
                        return ControllerManager::RETURN_RELOAD;
                    }
                }

                return [
                    'title' => 'Add',
                    'form' => $form,
                    'page' => $this
                ];
            }
        }



        public function formHasUpload(Form $form)
        {
            foreach($form as $field) {
                if ($field instanceof Form\Upload) return true;
            }
            return false;
        }

        public function handleEdit(Response $response, Request $request, $id, Handler $uploader = null)
        {
            try {

                if ($id && $this->provider instanceof ICanSave) {
                    $id = $this->decodeUid($id);

                    $object = $this->provider->findByUid($id);

                    if ($object) {

                        $form = $this->buildEditForm($request, $response, $object);

                        $this->prepareForm($form);

                        try {

                            if ($request->isPost() && $form->validate()) {
                                try {
                                    foreach ($this->provider->getFields(function (IField $field) {
                                        return !$field->isSurrogate();
                                    }) as $field) /** @var $field IField */ {

                                        $decorator = $this->decorateField($field);

                                        if ($decorator) {
                                            $field_value = $form->getValue($decorator->getFormField()->getName());
                                            $decorator->getField()->set($object, $field_value);
                                        }
                                    }
                                    if ($this->provider->saveEntity($object)) {
                                        $this->notifications->addMessage('Saved');
                                    }
                                } catch (\Exception $e) {
                                    echo $e->getMessage();
                                    die();
                                }

                                if ($request->hasParam('save_and_list')) {
                                    return Response\Factory::Redirect($response, $this->urlList());
                                } else {
                                    return ControllerManager::RETURN_RELOAD;
                                }

                            }
                        } catch (\Exception $e) {
                            $this->notifications->addError($e->getMessage());
                        }
                    } else {
                        return ControllerManager::NOT_FOUND;
                    }

                    return array_merge([
                        'title' => 'Edit',
                        'form' => $form,
                        'page' => $this,
                    ], $this->injectTemplateParams($object));
                }
            } catch (\Exception $e) {
                $this->notifications->addError($e->getMessage());

                return Response\Factory::Redirect($response, $this->urlList());
            }
        }

        protected function injectTemplateParams(\project5\Provider\IEntity $object)
        {
            return [];
        }

        public function handleView($id)
        {
            try {
                $object = $this->provider->findByUid($this->decodeUid($id));

                return array_merge([
                    'entity' => $object,
                    'page' => $this,
                ], $this->injectTemplateParams($object));
            } catch (\Exception $e) {
                $this->notifications->addError($e->getMessage());
            }
        }

        public function handleMview(Request $request, Response $response)
        {
            try {
                $ids = $request->getParam('mpk', []);
                if (is_array($ids) && !empty($ids)) {
                    $entities = new \SplObjectStorage();
                    foreach($ids as $uid) {
                        if ($entity = $this->provider->findByUid($this->decodeUid($uid))) {
                            $entities->attach($entity);
                        }
                    }

                    return [
                        'entities' => $entities,
                        'page' => $this,

                    ];
                } else {
                    $this->notifications->addError('Empty set');
                }
            } catch (\Exception $e) {
                $this->notifications->addError($e->getMessage());
                return Response\Factory::Redirect($response, $this->urlList());
            }
        }

        public function inFilter()
        {
            return $this->filter;
        }

        protected function dispatchHome(Request $request, Response $response, array $arguments = [])
        {
            // dispatch list if existed
            if ($this->routeList && $this->hasList()) return $this->routeList->dispatch($request, $response, $arguments);
            else return parent::dispatchHome($request, $response, $arguments);
        }

        protected function doSearch(ICanSearch $provider, $search)
        {
            if ((string)$search !== '') $provider->setSearchTerm($search);
        }

        /**
         * @var Form
         */
        public $filter;

        protected function dispatchRoute(Request $request, Response $response, array $arguments = [])
        {
            $provider = $this->getProvider();

            if ($provider instanceof ICanBeFiltered) {
                if ($request->persistParam('filter', 0, $this->ns)) {


                        if ($this->filter = $this->buildFilterForm($provider, $request, $response)) {

                            $this->prepareForm($this->filter);

                            if ($this->filter->validate(true)) {

                                $data = $this->filter->getData();

                                foreach ($provider->getFields() as $field) /** @var $field IField */ {
                                    $name = $field->getName();

                                    if (isset($data[$name]) && !empty($data[$name])) {

                                        if (is_array($data[$name])) {

                                            if (array_key_exists('from', $data[$name]) || array_key_exists('till', $data[$name])) {
                                                $provider->filter($field, new Range(
                                                    !empty($data[$name]['from']) ? $data[$name]['from'] : null,
                                                    !empty($data[$name]['till']) ? $data[$name]['till'] : null
                                                ));
                                            }
                                        } else {

                                            $provider->filter($field, new Equal($data[$name]));
                                        }
                                    }
                                }
                            }
                        }

                }

                if (!empty($this->groups)) {
                    if ($tab = $request->persistParam('tabfilter', $this->tabGroup, $this->ns)) {



                        if (isset($this->groups[$tab])) {
                            list($fields, $title) = $this->groups[$tab];
                            foreach ($fields as $name => $value) {
                                foreach ($provider->getFields() as $field) /** @var $field IField */ {
                                    if ($field->getName() == $name) {

                                        $provider->filter($field, new Equal($value));
                                    }
                                }
                            }
                            $this->tabGroup = $tab;
                        }
                    }
                }
            }


            return parent::dispatchRoute($request, $response, $arguments);
        }

        /**
         * @param IField $field
         * @return Decorator|null
         */
        public function decorateField(IField $field)
        {
            if ($field instanceof Boolean) {
                $decorator = new Switcher($field);
            } elseif ($field instanceof NumberField) {
                $decorator = new Number($field);
            } elseif ($field instanceof Relation) {
                // tries to found crud page from
                $page = null;
                try{
                    $front = $this->getRoot();


                    if ($front instanceof Group) {
                        foreach($front->getRoutes() as $route) {
                            if ($route instanceof Page) {
                                $arguments = $front->getAttributes($route);

                                if (isset($arguments['provider']) && $arguments['provider'] instanceof IProvider) {
                                    if ($arguments['provider'] == $field->getForeignProvider()) {
                                        $page = $route;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                } catch (\Exception $e) {
                    // ignore
                }

                $decorator = new Crud($field, $page);
            } else {
                $decorator = new Decorator($field);
            }

            if ($this->tabGroup && isset($this->groups[$this->tabGroup])) {
                list($fields, $title) = $this->groups[$this->tabGroup];

                if (isset($fields[$field->getName()])) {
                    $decorator->setFlag(Decorator::FLAG_HIDE_LIST);
                }
            }

            if (array_key_exists($field->getName(), $this->titles)) {
                $decorator->setTitle($this->titles[$field->getName()]);
            }

            return $decorator;
        }


        /**
         * Check if has rank functionality
         *
         * @return bool
         */
        public function hasRank()
        {
            if ($this->isEnabled(SELF::SERVICE_RANK) && $this->provider instanceof ICanRank) {
                return $this->provider->canRank();
            }

            return false;
        }
    }