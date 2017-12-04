<?php
    namespace project5\Form;

    use project5\Form;
    use project5\Upload\Handler;

    class Upload extends Field
    {
        private $handler;

        public function __construct($name, Handler $uploader, $title = null)
        {
            parent::__construct($name, $title);

            $this->handler = $uploader;
        }

        /**
         * @return Handler
         */
        public function getHandler()
        {
            return $this->handler;
        }

    }