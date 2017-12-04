<?php
    namespace project5;



    interface IFactory
    {

        /**
         * Create ne provider instance
         *
         * @param array $arguments
         * @return object
         */
        public function FactoryMethod(array $arguments);


    }