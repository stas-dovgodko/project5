<?php

    namespace project5;


    use project5\Form\Field;
    use project5\Form\IValidator;
    use Traversable;

    use StasDovgodko\Uri\Url;
    use project5\Web\Request;
    use project5\Web\Response;

    class Form implements \IteratorAggregate, \ArrayAccess, \JsonSerializable
    {
        private $priorityTop = 0;
        private $priorityBottom = 0;

        const METHOD_GET = 'GET';
        const METHOD_POST = 'POST';

        /**
         * Form action
         *
         * @var string
         */
        protected $action;

        /**
         * Form send method
         *
         * @var string
         */
        private $method;

        /**
         * Form post encode method
         *
         * @var string
         */
        private $encode;

        protected $_errors;

        private $requiredFields;

        protected $valid = false;

        private $_prefix;

        //protected $files;

        private $_hidden = array();

        /**
         * @var Request
         */
        protected $request;

        /**
         * @var \SplObjectStorage
         */
        protected $fields;

        private $defaults = [];
        private $values = [];

        /**
         * Form constructor
         *
         * @param string $action
         * @param string $method
         * @param string $encoding
         */
        public function __construct(Request $request, $action = null, $prefix = null)
        {
            $this->request = $request;

            $this->action = $action;
            $this->method = self::METHOD_POST;
            $this->encode = 'application/x-www-form-urlencoded';




            //$this->files   = $files;
            $this->_prefix  = $prefix;

            //$this->values         = $values;
            $this->errors         = array();
            $this->requiredFields = array();

            $this->fields = new \SplObjectStorage();

            $this->init();

        }

        /**
         * @return Request
         */
        public function getRequest()
        {
            return $this->request;
        }

        /**
         * @param Field|string $key
         * @param $value
         */
        public function setDefault($key, $value)
        {
            $field = $this->getField($key);

            if ($this->fields->contains($field)) {
                $data = $this->fields->offsetGet($field);
                $data['default'] = $value;
                $this->fields->offsetSet($field, $data);
            } else {
                throw new \UnexpectedValueException('Field "'.$key.'" missed in form');
            }
        }

        /**
         * @param Field|string $key
         * @return mixed
         */
        public function getDefault($key)
        {
            $field = $this->getField($key);

            if ($this->fields->contains($field)) {
                $data = $this->fields->offsetGet($field);
                return $data['default'];
            } else {
                throw new \UnexpectedValueException('Field "'.$key.'" missed in form');
            }
        }

        public function addValidator($key, IValidator $validator, $error_message = null)
        {
            $field = $this->getField($key);
            if ($this->fields->contains($field)) {
                $data = $this->fields->offsetGet($field);

                $error_params = [];
                if (!$error_message) {
                    $error_message = $validator->getErrorDefaultMessage($error_params);
                }

                $data['validators'][] = [$validator, $error_message, $error_params];
                $this->fields->offsetSet($field, $data);
            }
        }

        /**
         * @param Field $field
         * @throws \UnexpectedValueException
         * @return int
         */
        private function getPriority(Field $field)
        {
            if ($this->fields->contains($field)) {
                $data = $this->fields->offsetGet($field);
                return $data['priority'];
            } else {
                throw new \UnexpectedValueException('Field "'.$field->getName().'" missed in form');
            }
        }

        /**
         * @param Field $field
         * @throws \UnexpectedValueException
         * @param $priority
         * @return void
         */
        private function setPriority(Field $field, $priority)
        {
            if ($this->fields->contains($field)) {
                $data = $this->fields->offsetGet($field);
                $data['priority'] = $priority;
                $this->priorityTop = max($this->priorityTop, $priority);
                $this->priorityBottom = min($this->priorityBottom, $priority);
                $this->fields->offsetSet($field, $data);
            } else {
                throw new \UnexpectedValueException('Field "'.$field->getName().'" missed in form');
            }
        }



        public function moveJustBefore(Field $field, Field $base)
        {
            $start = $this->getPriority($base);

            foreach($this->fields as $candidate) {
                $priority = $this->getPriority($candidate);
                if ($priority <= $start) {
                    $this->setPriority($candidate, $priority-1);
                }
            }

            $this->setPriority($field, $start);
        }

        public function moveJustAfter(Field $field, Field $base)
        {
            $start = $this->getPriority($base);

            foreach($this->fields as $candidate) {
                $priority = $this->getPriority($candidate);
                if ($priority >= $start) {
                    $this->setPriority($candidate, $priority+1);
                }
            }

            $this->setPriority($field, $start);
        }

        public function replace(Field $field, Field $base = null)
        {
            if ($base === null) {
                $base = $this->getField($field->getName());
            }
            if ($this->fields->contains($base)) {
                $options = $this->fields->offsetGet($base);

                if (!$this->fields->contains($field)) {
                    $this->fields->attach($field, $options);
                } else {
                    $this->fields->offsetSet($field, $options);
                }
                $this->fields->detach($base);
            }
        }

        public function setRequest(Request $request)
        {
            $this->request = $request;
        }

        public function addError($message, $messageParams = [])
        {
            $this->_errors[] = [$message, $messageParams];
        }

        public function setPrefix($prefix)
        {
            $this->_prefix = $prefix;
        }

        public function getPrefix()
        {
            return $this->_prefix;
        }

        public function getFieldName($key)
        {
            if ($this->hasField($key)) {
                $field = $this->getField($key);
                if ($this->_prefix) {
                    return sprintf('%s_%s', $this->_prefix, $field->getName());
                } else {
                    return $field->getName();
                }
            } elseif (is_string($key) && isset($this->_hidden[$key])) {
                if ($this->_prefix) {
                    return sprintf('%s_%s', $this->_prefix, $key);
                } else {
                    return $key;
                }
            } else {
                throw new \UnexpectedValueException('Field "'.$key.'" missed in form');
            }
        }

        /**
         * @param Field|string $key
         * @throws \UnexpectedValueException
         * @return Field
         */
        protected function getField($key)
        {
            if ($key instanceof Field) {
                if ($this->fields->contains($key)) {
                    return $key;
                } else {
                    throw new \UnexpectedValueException('Field "'.$key->getName().'" missed in form');
                }
            } else {
                $key = (string)$key;
                foreach($this->fields as $field) {
                    if ($field->getName() === $key) {
                        return $field;
                    }
                }
                throw new \UnexpectedValueException('Field "'.$key.'" missed in form');
            }
        }

        /**
         * @param Field|string $key
         * @return bool
         */
        public function hasField($key)
        {
            if ($key instanceof Field) {
                if ($this->fields->contains($key)) {
                    return true;
                }
            } else {
                $key = (string)$key;
                foreach($this->fields as $field) {
                    if ($field->getName() === $key) {
                        return true;
                    }
                }
            }

            return false;
        }

        public function setValue($key, $value)
        {
            $field = $this->getField($key);

            $this->values[$field->getName()] = $value;
        }

        /**
         * @param Field|string $key
         * @return mixed
         */
        public function getValue($key)
        {
            try {
                $field = $this->getField($key);
            } catch (\UnexpectedValueException $e) {
                $field = null;
            }

            if ($field && isset($this->values[$field->getName()])) {
                return $this->values[$field->getName()];
            } else {
                try {
                    $field_name = $this->getFieldName($key);
                } catch (\UnexpectedValueException $e) {
                    $field_name = null;
                }


                if ($field_name) {

                    if ($this->request->hasParam($field_name)) {
                        return $this->request->getParam($field_name);
                    }
                } elseif (is_string($key) && isset($this->_hidden[$key])) {
                    return $this->_hidden[$key];
                }

                return $this->getDefault($key);
            }
        }

        public function getValues()
        {
            $args = func_get_args();
            $names = array();
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    $names = array_merge($names, $arg);
                } else {
                    if ($arg !== null) {
                        $names[] = $arg;
                    }
                }
            }

            if (!empty($names)) {
                $return = [];
                foreach ($names as $name) {
                    $return[] = $this->getValue($name);
                }
                return $return;
            } else {
                return $this->getData();
            }
        }

        public function hasErrors($key = null)
        {
            if ($key === null) return !empty($this->_errors);

            if (is_array($key)) {
                foreach($key as $one_key) {
                    if ($this->hasErrors($one_key)) {
                        return true;
                    }
                }
            } elseif ($field = $this->getField($key)) {
                $options = $this->fields->offsetGet($field);

                return !empty($options['errors']);
            }



            return null;
        }

        public function getErrors($key = null)
        {
            if ($key === null) return $this->_errors;
            if (is_array($key) || ($key instanceof \Traversable)) {
                $errors = [];
                foreach($key as $one_key) {
                    $errors = array_merge($errors, $this->getErrors($one_key));
                }
                return array_unique($errors);
            } elseif ($field = $this->getField($key)) {

                $options = $this->fields->offsetGet($field);

                $error = !empty($options['errors'])?$options['errors']:[];

                return array_map(function($e) {
                    return is_string($e) ? [(string)$e, []] : [(string)$e[0], $e[1]];
                }, $error);
            }



            return [];
        }

        public function setCsrf(Request $request)
        {
            $key = md5($this->action).'_csrf';

            if (!($csrf = $request->getSession()->get('csrf', $key))) {
                $csrf = md5(microtime(true));
                $request->getSession()->set('csrf', $key, $csrf);
            }
            $this->_hidden['csrf'] = $csrf;
        }

        public function addHidden($name, $value)
        {
            $this->_hidden[$name] = $value;

            return $this;
        }

        /**
         * @return array
         */
        public function getHidden()
        {
            return $this->_hidden;
        }

        protected function init()
        {

        }

        /**
         * Get form encode
         *
         * @return string
         */
        public function getEncode()
        {
            return $this->encode;
        }

        /**
         * Get form action
         *
         * @return string
         */
        public function getAction()
        {
            return $this->action;
        }

        /**
         * @return Url
         */
        public function getActionUrl()
        {
            if ($this->action) {
                $url = new Url($this->action);

                return $this->request->getFullUri()->resolve($url);
            } else {
                return $this->request->getFullUri();
            }
        }

        /**
         * Set form action
         *
         * @param string $action Form action
         *
         * @return self
         */
        public function setAction($action)
        {
            $this->action = $action;

            return $this;
        }

        /**
         * Get form method
         *
         * @return string
         */
        public function getMethod()
        {
            return $this->method;
        }

        /**
         * Set form method
         *
         * @param string $method Form method
         *
         * @return self
         */
        public function setMethod($method)
        {
            $this->method = $method;

            return $this;
        }

        /**
         * @return bool
         */
        public function isPost()
        {
            return $this->getMethod() == self::METHOD_POST;
        }

        /**
         * @param Field $field
         * @return bool
         */
        protected function validateField(Field $field, $ignoreRequired = false)
        {
            $valid = true;
            if ($this->fields->contains($field)) {

                $value = $this->getValue($field);

                $this->resetFieldError($field);
                // check for required
                if ($field->canBeEmpty() && empty($value)) {
                    // ignore
                } elseif (!$ignoreRequired && !$field->canBeEmpty() && empty($value)) {
                    $this->addFieldError($field, $field->getEmptyErrorMessage() ?: 'Required field');
                    $valid &= false;
                } else {

                    $options = $this->fields->offsetGet($field);

                    if (!empty($options['validators'])) {

                        foreach ($options['validators'] as list($validator, $message, $message_params)) {
                            /** @var $validator IValidator */
                            if (!$validator->validate($value, $message)) {

                                $this->addFieldError($field, $message, $message_params);

                                $valid &= false;
                            }
                        }
                    }
                }

                if ($valid) {
                    $method = 'validate' . ucfirst($field->getName());



                    if (method_exists($this, $method)) {

                        $errors = [];
                        $this->$method($value, $errors);
                        //call_user_func([$this, $method], $value, &$errors);

                        if (sizeof($errors) > 0) {
                            foreach($errors as $error) {
                                $this->addFieldError($field, $error);
                            }
                            $valid = false;
                        }
                    }
                }
            }

            return $valid && !$this->hasFieldError($field);
        }

        protected function resetFieldError(Field $field)
        {
            if ($this->fields->contains($field)) {
                $options = $this->fields->offsetGet($field);
                $options['errors'] = [];
                $this->fields->offsetSet($field, $options);
            }
        }

        /**
         * @param Field $field
         * @return bool|null
         */
        protected function hasFieldError(Field $field)
        {
            if ($this->fields->contains($field)) {
                $options = $this->fields->offsetGet($field);
                return count($options['errors']) > 0;
            }

            return null;
        }

        public function addFieldError($key, $message, $messageParams = [])
        {
            $field = $this->getField($key);
            if ($this->fields->contains($field)) {
                $options = $this->fields->offsetGet($field);
                $options['errors'][] = [$message, $messageParams];
                $this->fields->offsetSet($field, $options);
            }
        }

        protected function validateRequired($fields = array())
        {

        }

        protected function processUpload()
        {
            // check if form has upload fields
            $names = []; $fields = []; $valid = true;
            foreach($this->fields as $field) {
                $names[] = ($request_name = $this->getFieldName($field));
                $fields[$request_name] = $field;

                if ($field instanceof Form\Upload) {

                    $uploader = $field->getHandler();

                    $errors = [];
                    foreach($uploader->upload($this->request, $errors, [$this->getFieldName($field)]) as $name => $resource) {

                        $this->setValue($field, (string)$resource->getUri());
                    }

                    if (!empty($errors)) {
                        foreach($errors as $name => $messages) {
                            if (isset($fields[$name])) {
                                $field = $fields[$name];

                                foreach($messages as $message) $this->addFieldError($field, $message, []);
                            }
                        }

                        $valid = false;
                    }
                }
            }

            return $valid;
        }

        public function validate($ignoreRequired = false, $fields = array())
        {
            $this->_errors = [];

            $valid = $this->processUpload();

            if ($valid) {

                foreach (clone $this->fields as $entry) {
                    /* @var $entry Field */
                    if ($fields && !in_array($entry->getName(), $fields, true)) continue;

                    $valid &= $this->validateField($entry, $ignoreRequired);


                }

                if ($valid) {
                    $valid &= $this->_doValidate($ignoreRequired, $fields);
                }
            }


            return $valid;
        }

        public function append(Field $field, IValidator $validator = null, $error_message = null)
        {
            $priority = --$this->priorityBottom;
            $options = [
                'priority' => $priority,
                'validators' => [],
                'errors' => [],
                'default' => '',
            ];

            foreach ($field->getValidators($this) as $form_validator) {
                $error_params = [];
                $error_message = $form_validator->getErrorDefaultMessage($error_params);

                $options['validators'][] = [$form_validator, $error_message, $error_params];
            }

            if ($validator) {
                $error_params = [];
                if (!$error_message) {
                    $error_message = $validator->getErrorDefaultMessage($error_params);
                }

                $options['validators'][] = [$validator, $error_message, $error_params];
            }

            $this->fields->attach($field, $options);

            return $field;
        }



        protected function _doValidate($ignoreRequired = false, $fields = array())
        {
            return true;
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Retrieve an external iterator
         * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
         * @return Traversable An instance of an object implementing <b>Iterator</b> or
         * <b>Traversable</b>
         */
        public function getIterator()
        {
            $queue = new \SplPriorityQueue();

            foreach($this->fields as $field) {
                $queue->insert($field, $this->getPriority($field));
            }

            return $queue;
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Whether a offset exists
         * @link http://php.net/manual/en/arrayaccess.offsetexists.php
         * @param mixed $offset <p>
         * An offset to check for.
         * </p>
         * @return boolean true on success or false on failure.
         * </p>
         * <p>
         * The return value will be casted to boolean if non-boolean was returned.
         */
        public function offsetExists($offset)
        {
            foreach($this->fields as $field) {
                /** @var $field Field */
                if ($field->getName() === $offset) return true;
            }
            return false;
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to retrieve
         * @link http://php.net/manual/en/arrayaccess.offsetget.php
         * @param mixed $offset <p>
         * The offset to retrieve.
         * </p>
         * @return mixed Can return all value types.
         */
        public function offsetGet($offset)
        {
            foreach($this->fields as $field) {
                /** @var $field Field */
                if ($field->getName() === $offset) {
                    return $field;
                }
            }

        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to set
         * @link http://php.net/manual/en/arrayaccess.offsetset.php
         * @param mixed $offset <p>
         * The offset to assign the value to.
         * </p>
         * @param mixed $value <p>
         * The value to set.
         * </p>
         * @return void
         */
        public function offsetSet($offset, $value)
        {
            if ($value instanceof Field) {
                $field = clone $value;
                $field->setName($offset);

                $this->append($field);
            } else {
                throw new \UnexpectedValueException('Field instance expected');
            }
        }

        /**
         * (PHP 5 &gt;= 5.0.0)<br/>
         * Offset to unset
         * @link http://php.net/manual/en/arrayaccess.offsetunset.php
         * @param mixed $offset <p>
         * The offset to unset.
         * </p>
         * @return void
         */
        public function offsetUnset($offset)
        {
            foreach($this->fields as $field) {
                /** @var $field Field */
                if ($field->getName() === $offset) {
                    $this->fields->detach($field);
                    break;
                }
            }
        }

        public function getData()
        {
            $data = [];
            foreach($this->_hidden as $name => $value) {
                $data[$name] = $value;
            }
            foreach($this->fields as $field) {
                $data[$field->getName()] = $this->getValue($field);
            }

            return $data;
        }

        public function __toString()
        {
            return 'form';
        }

        public function jsonSerialize() {
            return $this->getData();
        }
    }


