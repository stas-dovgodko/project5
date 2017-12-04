<?php
namespace project5\DI;

use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Loader\FileLoader;


class YamlFileLoader extends FileLoader implements LoggerAwareInterface
{
    /**
     * @param $container Container
     */
    protected $container;

    const VER = 1;

    use LoggerAwareTrait;

    protected $dir;
    protected $prefix;

    protected $loaded = [];

    protected $useParameterBag;

    protected $imported = [];

    public function __construct(Container $container, $dir, $prefix = null)
    {
        parent::__construct($container, $container);

        $this->container = $container;

        $this->dir = $dir;
        $this->prefix = $prefix;

        $this->useParameterBag = $container->getParameterBag()->getBaseParameterBag();
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);

        $content = $this->loadFile($path);

        $this->container->addResource(new FileResource($path));

        // empty file
        if (null === $content) {
            return;
        }

        // imports
        $this->parseImports($content, $path);

        if (isset($content['use_parameters'])) {
            foreach ($content['use_parameters'] as $key => $value) {
                $this->useParameterBag->set($key, $this->resolveServices($value));

                if (!isset($content['parameters'][$key]) && !$this->container->hasParameter($key)) {
                    //$this->container->setParameter($key, $this->resolveServices($value));
                }
            }
        }

        // parameters
        if (isset($content['parameters'])) {
            if (!is_array($content['parameters'])) {
                throw new InvalidArgumentException(sprintf('The "parameters" key should contain an array in %s. Check your YAML syntax.', $resource));
            }

            foreach ($content['parameters'] as $key => $value) {
                $this->container->setParameter($key, $this->resolveServices($value));
            }
        }

        // extensions
        $this->loadFromExtensions($content);

        // services

        if (isset($content['services'])) {
            if (!is_array($content['services'])) {
                throw new InvalidArgumentException(sprintf('The "services" key should contain an array in %s. Check your YAML syntax.', $file));
            } else {
                foreach ($content['services'] as $id => $service) {
                    $this->parseDefinition($id, $service, $resource);
                }
            }
        }
    }



    protected function loadFile($file, &$uses = [])
    {
        if ($this->dir) {

            $hash = hash_init('sha1');

            hash_update($hash,  self::VER);
            hash_update($hash,  $file);
            hash_update($hash,  filemtime($file));

            $tmpname = $this->dir ? rtrim($this->dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . preg_replace('/[^\w]+/', '_', basename($file)).'_'.hash_final($hash) : null;
        } else {
            $tmpname = null;
        }

        if ($tmpname && is_file($tmpname) && filesize($tmpname) > 0) {
            $content = require($tmpname);


        } else {
            $parser = new YamlParser();
            $content = $parser->parse(file_get_contents($file));

            if (is_writable(dirname($tmpname))) {
                file_put_contents($tmpname, '<' . '?php return ' . var_export($content, true) . ';');
            } elseif ($this->logger) {
                $this->logger->error('Dir "'.dirname($tmpname).'" not writable');
            }
        }

        // load uses
        if (isset($content['use'])) {
            $use = (!is_array($content['use'])) ? [$content['use']] : $content['use'];

            foreach($use as $usename) {

                $this->container->useServices($usename);


                if (!isset($uses[$usename])) {

                    $uses[$usename] = $service_path = __DIR__ . '/../../services/service.' . $usename . '.yml';


                    foreach ($this->loadFile($service_path, $uses) as $key => $loaded) {

                        if (!is_array($loaded)) {
                            continue;
                        }

                        $inject = false;
                        if ($key === 'parameters') {
                            $key = 'use_parameters';
                        } elseif ($key === 'inject') {
                            $inject = true;
                            $key = 'services';
                        }


                        if (isset($content[$key])) {
                            $content[$key] = ($inject) ? array_merge($content[$key], $loaded) : array_merge($loaded, $content[$key]);
                        } else {
                            $content[$key] = $loaded;
                        }
                    }


                }


            }

            unset($content['use']);
        }

        foreach ($content as $namespace => $data) {

            if (in_array($namespace, array('imports', 'parameters', 'services', 'use_parameters'))) {
                continue;
            }

            if (!$this->container->hasExtension($namespace)) {
                $extensionNamespaces = array_filter(array_map(function ($ext) { return $ext->getAlias(); }, $this->container->getExtensions()));
                throw new InvalidArgumentException(sprintf(
                    'There is no extension able to load the configuration for "%s" (in %s). Looked for namespace "%s", found %s',
                    $namespace,
                    $file,
                    $namespace,
                    $extensionNamespaces ? sprintf('"%s"', implode('", "', $extensionNamespaces)) : 'none'
                ));
            }
        }

        return $this->loaded[$file] = $content;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && in_array(pathinfo($resource, PATHINFO_EXTENSION), array('yml', 'yaml'), true);
    }

    /**
     * Parses all imports.
     *
     * @param array  $content
     * @param string $file
     */
    private function parseImports($content, $file)
    {
        if (!isset($content['imports'])) {
            return;
        }

        if (!is_array($content['imports'])) {
            throw new InvalidArgumentException(sprintf('The "imports" key should contain an array in %s. Check your YAML syntax.', $file));
        }

        foreach ($content['imports'] as $import) {
            if (!is_array($import)) {
                throw new InvalidArgumentException(sprintf('The values in the "imports" key should be arrays in %s. Check your YAML syntax.', $file));
            }

            $this->setCurrentDir(dirname($file));

            $import_abs_name = dirname($file) . DIRECTORY_SEPARATOR . $import['resource']; // this not actually filename!

            if (!in_array($import_abs_name, $this->imported)) {

                try {
                    $this->import($import['resource'], 'import', isset($import['ignore_errors']) ? (bool)$import['ignore_errors'] : false, $file);
                } catch (FileLoaderImportCircularReferenceException $e) {
                    // just ignore
                }

                $this->imported[] = $import_abs_name;
            }
        }
    }

    /**
     * Parses a definition.
     *
     * @param string $id
     * @param array  $service
     * @param string $file
     *
     * @throws InvalidArgumentException When tags are invalid
     */
    private function parseDefinition($id, $service, $file)
    {
        if (is_string($service) && 0 === strpos($service, '@')) {
            $this->container->setAlias($id, substr($service, 1));

            return;
        }

        if (!is_array($service)) {
            throw new InvalidArgumentException(sprintf('A service definition must be an array or a string starting with "@" but %s found for service "%s" in %s. Check your YAML syntax.', gettype($service), $id, $file));
        }

        if (isset($service['alias'])) {
            $public = !array_key_exists('public', $service) || (bool) $service['public'];
            $this->container->setAlias($id, new Alias($service['alias'], $public));

            return;
        }

        if (isset($service['parent'])) {
            $definition = new DefinitionDecorator($service['parent']);
        } else {
            $definition = new Definition();
        }

        if (isset($service['class'])) {
            $definition->setClass($service['class']);
        }

        if (isset($service['scope']) && $service['scope'] === 'prototype') {
            $definition->setShared(true);
        }

        if (isset($service['synthetic'])) {
            $definition->setSynthetic($service['synthetic']);
        }

        if (isset($service['synchronized'])) {
            trigger_error(sprintf('The "synchronized" key in file "%s" is deprecated since version 2.7 and will be removed in 3.0.', $file), E_USER_DEPRECATED);
            $definition->setSynchronized($service['synchronized'], 'request' !== $id);
        }

        if (isset($service['lazy'])) {
            $definition->setLazy($service['lazy']);
        }

        if (isset($service['shared'])) {
            $definition->setShared($service['shared']);
        }

        if (isset($service['public'])) {
            $definition->setPublic($service['public']);
        }

        if (isset($service['abstract'])) {
            $definition->setAbstract($service['abstract']);
        }

        if (isset($service['factory'])) {
            if (is_string($service['factory'])) {
                if (strpos($service['factory'], ':') !== false && strpos($service['factory'], '::') === false) {
                    $parts = explode(':', $service['factory']);
                    $definition->setFactory(array($this->resolveServices('@'.$parts[0]), $parts[1]));
                } else {
                    $definition->setFactory($service['factory']);
                }
            } else {
                $definition->setFactory(array($this->resolveServices($service['factory'][0]), $service['factory'][1]));
            }
        }

        if (isset($service['factory_class'])) {
            trigger_error(sprintf('The "factory_class" key in file "%s" is deprecated since version 2.6 and will be removed in 3.0. Use "factory" instead.', $file), E_USER_DEPRECATED);
            $definition->setFactoryClass($service['factory_class']);
        }

        if (isset($service['factory_method'])) {
            trigger_error(sprintf('The "factory_method" key in file "%s" is deprecated since version 2.6 and will be removed in 3.0. Use "factory" instead.', $file), E_USER_DEPRECATED);
            $definition->setFactoryMethod($service['factory_method']);
        }

        if (isset($service['factory_service'])) {
            trigger_error(sprintf('The "factory_service" key in file "%s" is deprecated since version 2.6 and will be removed in 3.0. Use "factory" instead.', $file), E_USER_DEPRECATED);
            $definition->setFactoryService($service['factory_service']);
        }

        if (isset($service['file'])) {
            $definition->setFile($service['file']);
        }

        if (isset($service['arguments'])) {
            $definition->setArguments($this->resolveServices($service['arguments']));
        }

        if (isset($service['properties'])) {
            $definition->setProperties($this->resolveServices($service['properties']));
        }

        if (isset($service['configurator'])) {
            if (is_string($service['configurator'])) {
                $definition->setConfigurator($service['configurator']);
            } else {
                $definition->setConfigurator(array($this->resolveServices($service['configurator'][0]), $service['configurator'][1]));
            }
        }

        if (isset($service['attach'])) {
            if (is_string($service['attach'])) {
                $handlers = [$id => $service['attach']];
            } else {
                $handlers = (array)$service['attach'];
            }

            $pre_configorator = $definition->getConfigurator(); $parameterBag = $this->container->getParameterBag();

            $definition->setConfigurator(function($object) use($definition, $handlers, $pre_configorator, $parameterBag) {

                if (is_callable($pre_configorator)) call_user_func_array($pre_configorator, func_get_args());

                foreach($handlers as $tag => $handler)
                {
                    $taggedServices = $this->container->findTaggedServiceIds($tag);

                    foreach ($taggedServices as $injected_id => $tags) {
                        foreach ($tags as $attributes) {

                            try {
                                $injected_service = $this->container->get($injected_id);
                            } catch (ServiceCircularReferenceException $e) {
                                $injected_service = $this->container->createService($this->container->getDefinition($injected_id), $injected_id);
                            }


                            $object->$handler(
                                $injected_service,
                                $attributes ? $parameterBag->resolveValue($attributes): []
                            );
                        }

                    }
                }

            });
        }

        if (isset($service['calls'])) {
            if (!is_array($service['calls'])) {
                throw new InvalidArgumentException(sprintf('Parameter "calls" must be an array for service "%s" in %s. Check your YAML syntax.', $id, $file));
            }

            foreach ($service['calls'] as $call) {
                if (isset($call['method'])) {
                    $method = $call['method'];
                    $args = isset($call['arguments']) ? $this->resolveServices($call['arguments']) : array();
                } else {
                    $method = $call[0];
                    $args = isset($call[1]) ? $this->resolveServices($call[1]) : array();
                }

                $definition->addMethodCall($method, $args);
            }
        }

        if (isset($service['tags'])) {
            if (!is_array($service['tags'])) {
                throw new InvalidArgumentException(sprintf('Parameter "tags" must be an array for service "%s" in %s. Check your YAML syntax.', $id, $file));
            }

            foreach ($service['tags'] as $tag) {
                if (!is_array($tag)) {
                    throw new InvalidArgumentException(sprintf('A "tags" entry must be an array for service "%s" in %s. Check your YAML syntax.', $id, $file));
                }

                if (!isset($tag['name'])) {
                    throw new InvalidArgumentException(sprintf('A "tags" entry is missing a "name" key for service "%s" in %s.', $id, $file));
                }

                $name = $tag['name'];
                unset($tag['name']);

                foreach ($tag as $attribute => $value) {
                    if (!is_scalar($value) && null !== $value) {
                        throw new InvalidArgumentException(sprintf('A "tags" attribute must be of a scalar-type for service "%s", tag "%s", attribute "%s" in %s. Check your YAML syntax.', $id, $name, $attribute, $file));
                    }
                }

                $definition->addTag($name, $tag);
            }
        }

        if (isset($service['decorates'])) {
            $renameId = isset($service['decoration_inner_name']) ? $service['decoration_inner_name'] : null;
            $definition->setDecoratedService($service['decorates'], $renameId);
        }

        $this->container->setDefinition($id, $definition);
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
     * Loads from Extensions.
     *
     * @param array $content
     */
    private function loadFromExtensions($content)
    {
        foreach ($content as $namespace => $values) {
            if (in_array($namespace, array('imports', 'parameters', 'use_parameters', 'services'))) {
                continue;
            }

            if (!is_array($values)) {
                $values = array();
            }

            $this->container->loadFromExtension($namespace, $values);
        }
    }
}