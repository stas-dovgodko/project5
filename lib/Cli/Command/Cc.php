<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 01.01.15
 * Time: 15:16
 */
namespace project5\Cli\Command;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FlushableCache;
use project5\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * HelpCommand displays the help for a given command.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Cc extends Command
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var CacheProvider[]
     */
    private $cacheProviders = [];

    private $dirs = [];

    public function __construct(Application $app, array $cacheProviders = [], array $dirs = [])
    {
        $this->app = $app;
        foreach($cacheProviders as $provider)
        {
            $this->addCacheProvider($provider);
        }
        foreach($dirs as $dir) {
            $this->addDir($dir);
        }

        parent::__construct('clear-cache');
    }

    public function addCacheProvider(CacheProvider $provider)
    {

    }

    public function addDir($dir)
    {
        $this->dirs[] = $dir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('clear-cache')
            ->setAliases(['cc', 'clearcache', 'clear'])
            ->setDefinition(array(

            ))
            ->setDescription('Clear all caches')
            ->setHelp('')
        ;
    }

    protected function clearDir($dir, OutputInterface $output)
    {
        $output->writeln(sprintf('Tries to clean %s dir', $dir));

        foreach (glob(rtrim($dir, DIRECTORY_SEPARATOR.'\\') . DIRECTORY_SEPARATOR . "*") as $filename) {

            if (is_dir($filename) ) {
                $this->clearDir($filename, $output);

            }

            if (@unlink($filename)) $output->writeln(sprintf('File|dir %s deleted', $filename));
            else $output->writeln(sprintf('Can\'t delete %s file|dir', $filename));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach($this->cacheProviders as $cache_provider)
        {
            $stats = $cache_provider->getStats(); $before = isset($stats['memory_available']) ? $stats['memory_available'] : null;

            $output->writeln(sprintf('Tries to clean %s cache provider', get_class($cache_provider)));

            if ($before !== null) {
                $output->writeln(sprintf('Memory available before: %s', $before));
            }

            if ($cache_provider instanceof FlushableCache) {
                $state = $cache_provider->flushAll(); $method = 'flush';
            } elseif ($cache_provider instanceof ClearableCache) {
                $state = $cache_provider->deleteAll(); $method = 'clear';
            } else {
                $state = false;  $method = 'ignore';
            }

            if ($state) {
                $output->write('Successfully clear ['.$method.'] "'.get_class($cache_provider).'" cache provider', true);
            } else {
                $output->write('Fault['.$method.'] to clear "'.get_class($cache_provider).'" cache provider', true);
            }

            $stats = $cache_provider->getStats();
            $after = isset($stats['memory_available']) ? $stats['memory_available'] : null;
            if ($after !== null) {
                $output->writeln(sprintf('Memory available after: %s', $after));
            }
        }

        $dirs = array_unique($this->dirs);
        foreach($dirs as $dir)
        {
            $this->clearDir($dir, $output);
        }

        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                $output->write('Successfully clear opcache', true);
            } else {
                $output->write('Fault to clear opcache (return false)', true);
            }
        } else {
            $output->write('Ignore opcache (does not found)', true);
        }
    }
}
