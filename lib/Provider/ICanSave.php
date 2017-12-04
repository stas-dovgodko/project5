<?php
    namespace project5\Provider;

    interface ICanSave {

        /**
         * @param IEntity $entity
         * @return bool
         */
        public function saveEntity(IEntity $entity);

        /**
         * @return bool
         */
        public function canSave();
    }