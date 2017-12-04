<?php
    namespace project5\Crud;

    use project5\IProvider,
        project5\Provider\Aggregator;

    use project5\Provider\IField;
    use project5\Web\Response,
        project5\Web\Request,
        project5\Web\Controller;


    trait IHasReportMethods
    {
        protected function _init()
        {
            if ($this->hasReport() && $this instanceof Page) {
                foreach($this->listReports() as $report_name => $report_title) {
                    $this->addRoute('report/:name', new Controller('report', $this), $this->_reportRuleName($report_name));
                }
            }
        }

        protected function _reportRuleName($name)
        {
            return sprintf('report_%s', $name);
        }

        /**
         * @param IProvider $provider
         * @param $name
         * @return [Aggegator,[]]
         */
        abstract protected function _report(IProvider $provider, $name);

        public function handleReport(Response $response, Request $request, Controller $controller, $name)
        {
            $provider = $this->getProvider();

            list($aggrerator, $plots) = $this->_report($provider, $name);


            if ($aggrerator instanceof Aggregator)
            {
                // prepare donut data


                $this->render($response, 'Crud/report.twig', $params = [
                    'title' => 'List',
                    'page' => $this,
                    'controller' => $controller,
                    'provider' => $provider,
                    'aggregator' => $aggrerator,
                    'plots' => $plots,
                    'sortable' => [],
                    'tab' => 'report_'.$name,
                ]);
            }
        }

        protected function _pieData(IProvider $provider, IField $titleField, IField $valueField)
        {
            $data = [];
            foreach($provider as $entity) {
                $data[] = [
                    'label' => $titleField->get($entity),
                    'value' => $valueField->get($entity),
                ];
            }
            return $data;
        }

        public function hasReport()
        {
            return true;
        }

        public function urlReport($name, $params = [])
        {
            $route_name = $this->_reportRuleName($name);
            if ($this->hasRoute($route_name)) {
                return $this->getRoute($route_name)->uri(['name' => $name] + $params);
            }
        }

        abstract public function listReports();

    }