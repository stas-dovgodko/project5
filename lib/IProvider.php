<?php
    namespace project5;

    use project5\Provider\IField;
    use project5\Provider\IEntity;

    interface IProvider {

        /**
         * List of IField's
         *
         * @param callable $filter
         * @return \Traversable|IField[]
         */
        public function getFields(callable $filter = null);

        /**
         * @param $uid
         * @return IEntity|null
         */
        public function findByUid($uid);

        /**
         * IEntity's
         *
         * @return \Traversable|IEntity[]
         */
        public function iterate();

        /**
         * Create empty entity
         *
         * @return IEntity
         */
        public function createEntity();
    }