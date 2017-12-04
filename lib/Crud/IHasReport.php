<?php
    namespace project5\Crud;

    use project5\Web\Response,
        project5\Web\Request,
        project5\Web\Controller;

    interface IHasReport
    {
        public function handleReport(Response $response, Request $request, Controller $controller, $name);

        public function hasReport();

        public function urlReport($name);

        public function listReports();
    }