<?php
namespace project5;

use Psr\Log\LoggerAwareTrait as SuperLoggerAwareTrait;

trait LoggerAwareTrait
{
    use SuperLoggerAwareTrait;

    /**
     * @return \Psr\Log\LoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Logs the method call or the executed SQL statement.
     *
     * @param string $msg Message to log.
     */
    public function log($msg)
    {
        $logger = $this->getLogger();

        if ($msg && $logger) {
            $backtrace = debug_backtrace();


            $class = get_called_class();

            $calling = $class;
            $i = 1;
            $stackSize = count($backtrace);
            do {
                $callingMethod = $backtrace[$i]['function'];
                $callingClass = $backtrace[$i]['class'];
                if ($callingClass === $class && $callingMethod !== "log") {
                    $calling = $class.'::'.$callingMethod;
                    break;
                }
                $i++;
            } while ($i < $stackSize);

            $logger->debug($msg, [$calling]);
        }
    }
}
