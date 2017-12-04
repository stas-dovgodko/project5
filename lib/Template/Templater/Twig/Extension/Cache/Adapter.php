<?php
namespace project5\Template\Templater\Twig\Extension\Cache;

use Asm89\Twig\CacheExtension\CacheProvider\DoctrineCacheAdapter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Doctrine\Common\Cache\Cache;

class Adapter extends DoctrineCacheAdapter implements LoggerAwareInterface {
    use LoggerAwareTrait;

    protected $times = [];

    protected $adapter;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->adapter = get_class($cache);
        parent::__construct($cache);
    }

    /**
     * {@inheritDoc}
     */
    public function fetch($key)
    {
        if ($this->logger) {
            $t1 = microtime(true);
        }
        $return = parent::fetch($key);

        if ($this->logger) {
            if ($return === false) {
                $this->times[$key] = $t1;
                $this->logger->warning('MISS twig cache', ['key' => $key, 'adapter' => $this->adapter]);
            } else {
                $this->logger->debug('HIT twig cache', ['key' => $key, 'ms' => round((microtime(true) - $t1) * 1000), 'adapter' => $this->adapter]);
            }
        }

        return $return;
    }
    /**
     * {@inheritDoc}
     */
    public function save($key, $value, $lifetime = 0)
    {


        $return = parent::save($key, $value, $lifetime);

        if ($this->logger && isset($this->times[$key])) {
            $this->logger->warning('STORE twig cache', ['key' => $key, 'ms' => round((microtime(true) - $this->times[$key]) * 1000), 'lifetime' => $lifetime, 'adapter' => $this->adapter]);
            unset($this->times[$key]);
        }

        return $return;
    }
}