<?php
namespace project5\DI;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class Container extends ContainerBuilder implements FileLocatorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $paths;

    /**
     * @var callable[]
     */
    protected $onCompile = [];

    protected $uses = [];


    /**
     * Gets the service container parameter bag.
     *
     * @return \project5\DI\ParameterBag A ParameterBagInterface instance
     */
    public function getParameterBag()
    {
        return $this->parameterBag;
    }

    public function configure($filename)
    {
        ini_set('xdebug.max_nesting_level', 5000);

        if ($this->hasParameter('compile.dir')) {
            $bag = $this->getParameterBag();


            $compile_dir = $bag->resolveValue($bag->get('compile.dir'));
        } else {
            $compile_dir = null;
            $this->setParameter('compile.dir', null);
        }

        $yaml_loader = new YamlFileLoader($this, $compile_dir);
        if ($this->logger) {
            $yaml_loader->setLogger($this->logger);
        }

        $loaderResolver = new LoaderResolver(
            [
                new JsonFileLoader($this, $compile_dir),
                $yaml_loader,
                new PhpFileLoader($this, $this)
            ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        $delegatingLoader->load($filename);

        return $this;
    }

    /**
     * @return $this 'this' is support
     */
    public function getThisService()
    {
        return $this;
    }





    public function addOnCompile(callable $callback)
    {
        $this->onCompile[] = $callback;
    }

    public function addExtension($name, callable $callback)
    {
        $this->registerExtension(new ExtensionHandler($this, $name, $callback));
    }

    /**
     * Compiles the container.
     *
     * This method passes the container to compiler
     * passes whose job is to manipulate and optimize
     * the container.
     *
     * The main compiler passes roughly do four things:
     *
     *  * The extension configurations are merged;
     *  * Parameter values are resolved;
     *  * The parameter bag is frozen;
     *  * Extension loading is disabled.
     *
     * @api
     */
    public function compile()
    {
        $is_compiled = $this->isFrozen();
        
        if (false && !$is_compiled) {
            $use_container = new Container([__DIR__ . '/../../services/']);
            $use_container->uses = &$this->uses;

            $configured = [];
            while($use = array_shift($use_container->uses)) {

                if (!in_array($use, $configured)) {
                    $use_container->configure('service.' . $use . '.yml');

                    $configured[] = $use;
                }
                //echo $use;
            }

            foreach($use_container->getDefinitions() as $id => $definition) {
                if (!$this->hasDefinition($id)) $this->setDefinition($id, $definition);
            }

            foreach($use_container->getAliases() as $alias => $id) {
                if (!$this->hasAlias($id)) $this->setAlias($alias, $id);
            }

            foreach($use_container->getParameterBag()->all() as $key => $value) {
                if (!$this->hasParameter($key)) $this->setParameter($key, $value);
            }

            foreach ($use_container->getExtensions() as $name => $extension) {
                if (!$this->hasExtension($name)) {
                    $this->registerExtension($extension);
                }
            }
        }
        
        parent::compile();

        if (!$is_compiled && $this->onCompile) {
            array_walk($this->onCompile, 'call_user_func');
        }
    }





    /**
     * Constructor.
     *
     * @param string|array $paths A path or an array of paths where to look for resources
     */
    public function __construct($paths = array())
    {
        $this->paths = (array) $paths;

        parent::__construct(new \project5\DI\ParameterBag(new ParameterBag()));




        //$this->addCompilerPass($this);
    }

    /**
     * Add using service name
     * 
     * @param $name
     * @return $this
     */
    public function useServices($name)
    {
        if (!in_array($name, $this->uses, true)) $this->uses[] = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = true)
    {
        if ('' == $name) {
            throw new \InvalidArgumentException('An empty file name is not valid to be located.');
        }

        if ($this->isAbsolutePath($name)) {
            if (!file_exists($name)) {
                throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $name));
            }

            return $name;
        }

        $filepaths = array();
        if (null !== $currentPath && file_exists($file = $currentPath.DIRECTORY_SEPARATOR.$name)) {
            if (true === $first) {
                return $file;
            }
            $filepaths[] = $file;
        }

        foreach ($this->paths as $path) {
            if (file_exists($file = $path.DIRECTORY_SEPARATOR.$name)) {
                if (true === $first) {
                    return $file;
                }
                $filepaths[] = $file;
            }
        }

        if (!$filepaths) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not exist (in: %s%s).', $name, null !== $currentPath ? $currentPath.', ' : '', implode(', ', $this->paths)));
        }

        return array_values(array_unique($filepaths));
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string $file A file path
     *
     * @return bool
     */
    private function isAbsolutePath($file)
    {
        if ($file[0] === '/' || $file[0] === '\\'
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && $file[1] === ':'
                && ($file[2] === '\\' || $file[2] === '/')
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
        ) {
            return true;
        }

        return false;
    }

    public function hasExtension($name)
    {
        if (!parent::hasExtension($name)) {
            // find
            $this->registerExtension(new LazyExtensionHandler($this, $name));
        }

        return parent::hasExtension($name);
    }
}