<?php



namespace project5\DI {


    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\DependencyInjection\Definition;
    use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
    use Symfony\Component\DependencyInjection\Reference;
    use Symfony\Component\ExpressionLanguage\Expression;

    class LazyExtensionHandler implements ExtensionInterface
    {
        private $name;
        private $container;

        private $inited = false;
        private $setup;

        public function __construct(Container $container, $name)
        {
            $this->container = $container;
            $this->name = $name;
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
            if ($this->container->hasDefinition($this->name)) {
                $definition = $this->container->getDefinition($definition_id = $this->name);
            } elseif ($this->container->hasAlias($this->name)) {
                $definition = $this->container->getDefinition($definition_id = (string)$this->container->getAlias($this->name));
            } else {
                $definition = null;
            }

            if ($definition) {
                $class = $definition->getClass();

                if ($class) {
                    $reflection = new \ReflectionClass($class);
                    if ($reflection->implementsInterface(IContainer::CLASS)) {

                        if (!$this->inited) {
                            $this->setup = call_user_func([$class, 'Setup'], $this->container, $this->name);
                            $this->inited = true;
                        }

                        foreach ($config as $section) {

                            call_user_func([$class, 'Inject'], $definition, $this->resolveServices($section), $container, $this->setup);
                        }
                    }
                }
            }
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
            return $this->name;
        }
    }
}
