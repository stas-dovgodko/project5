<?php
    namespace project5\Provider;



    interface ICanDelete {

        /**
         * @param IEntity $entity
         * @return bool
         */
        public function deleteEntity(IEntity $entity);

        /**
         * @param string $uid
         * @return bool
         */
        public function deleteByUid($uid);

        /**
         * @return bool
         */
        public function canDelete();
    }