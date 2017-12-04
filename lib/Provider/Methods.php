<?php
    namespace project5\Provider;

    use project5\IProvider;

    trait Methods
    {
        /**
         * @param array $data
         * @param IEntity $entity
         * @return IEntity
         */
         public function populateEntity(array $data, IEntity $entity = null)
         {
             /** @var $this IProvider */
             if ($entity === null) {
                 $entity = $this->createEntity();
             }

             $fields = $this->getFields();

             foreach($data as $name => $value) {
                 foreach($fields as $field) /** @var $field IField */
                 {
                     if (strtolower($name) == strtolower($field->getName())) {
                         $field->set($entity, $value);
                         break;
                     }
                 }
             }

             return $entity;
         }

        /**
         * @param IField|string $field
         * @return IField|null
         */
        public function getField($field)
        {

            foreach($this->getFields() as $candidate) {
                if ($candidate === $field) {
                    return $candidate;
                } elseif (is_string($field) && $candidate->getName() === $field) {
                    return $candidate;
                }
            }

            return null;
        }

        /**
         * @param IEntity $entity
         * @param string, string $name
         * @return array
         */
        public function getFieldValues(IEntity $entity, $name)
        {
            $names = func_get_args(); array_shift($names);

            $fields = $this->getFields();
            $return = array();
            foreach($names as $name) {
                $value = null;
                foreach($fields as $field) /** @var $field IField */
                {
                    if (strtolower($name) == strtolower($field->getName())) {
                        $value = $field->get($entity);
                        break;
                    }
                }
                $return[] = $value;
            }

            return $return;
        }

        /**
         * @return bool
         */
        public function canBeCounted()
        {
            return true;
        }
    }