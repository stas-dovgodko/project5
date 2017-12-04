<?php
    namespace project5\Form;

    class Openselect extends Field {
        protected $callback;

        public function __construct($name, callable $callback, $title = null)
        {
            parent::__construct($name, $title);

            $this->callback = $callback;
        }

        public function getData($page = 1, $page_limit = 10, $search = null)
        {
            return call_user_func($this->callback, $page, $page_limit, $search);
        }
    }