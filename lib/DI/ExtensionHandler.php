<?php



namespace project5\DI {


    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
    use Symfony\Component\DependencyInjection\Reference;
    use Symfony\Component\ExpressionLanguage\Expression;

    class ExtensionHandler implements ExtensionInterface
    {
        private $_container;
        private $_name;

        /**
         * @var callable
         */
        private $_callback;
        public function __construct(Container $container, $name, callable $callback)
        {
            $this->_container = $container;
            $this->_name = $name;
            $this->_callback = $callback;
        }

        /**
         * Resolves services.
         *
         * @param string|array $value
         *
         * @return array|string|Reference
         */
        private function resolveServices($value)
        {
            if (is_array($value)) {
                $value = array_map(array($this, 'resolveServices'), $value);
            } elseif (is_string($value) &&  0 === strpos($value, '@=')) {
                return new Expression(substr($value, 2));
            } elseif (is_string($value) &&  0 === strpos($value, '@')) {
                if (0 === strpos($value, '@@')) {
                    $value = substr($value, 1);
                    $invalidBehavior = null;
                } elseif (0 === strpos($value, '@?')) {
                    $value = substr($value, 2);
                    $invalidBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
                } else {
                    $value = substr($value, 1);
                    $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
                }

                if ('=' === substr($value, -1)) {
                    $value = substr($value, 0, -1);
                    $strict = false;
                } else {
                    $strict = true;
                }

                if (null !== $invalidBehavior) {
                    $value = new Reference($value, $invalidBehavior, $strict);
                }
            }

            return $value;
        }

        /**
         * Loads a specific configuration.
         *
         * @param array $config An array of configuration values
         * @param ContainerBuilder $container A ContainerBuilder instance
         *
         * @throws \InvalidArgumentException When provided tag is not defined in this extension
         *
         * @api
         */
        public function load(array $config, ContainerBuilder $container)
        {
            foreach($config as $services) {
                call_user_func($this->_callback, $this->_container, array_map(array($this, 'resolveServices'), $services));
            }


        }

        /**
         * Returns the namespace to be used for this extension (XML namespace).
         *
         * @return string The XML namespace
         *
         * @api
         */
        public function getNamespace()
        {
            // TODO: Implement getNamespace() method.
        }

        /**
         * Returns the base path for the XSD files.
         *
         * @return string The XSD base path
         *
         * @api
         */
        public function getXsdValidationBasePath()
        {
            // TODO: Implement getXsdValidationBasePath() method.
        }

        /**
         * Returns the recommended alias to use in XML.
         *
         * This alias is also the mandatory prefix to use when using YAML.
         *
         * @return string The alias
         *
         * @api
         */
        public function getAlias()
        {
            return $this->_name;
        }
    }
}
