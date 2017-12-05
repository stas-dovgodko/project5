<?php
namespace project5\Web;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use project5\Web\Filter\Chain;
/**
 * Abstract filter support superclass
 *
 *
 */
abstract class Filter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @return Response|void
     */
    protected function preFilter(Request $request, Response $response, Chain $filterChain)
    {

    }

    /**
     * Pre filter method
     *
     * For process the rest of filter chain this code should be call inside:
     * <code>
     * $filterChain->doPreFilter ();
     * </code>
     *
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @return Response|null
     */
    public function doPreFilter(Request $request, Response $response, Chain $filterChain)
    {
        $return = $this->preFilter($request, $response, $filterChain);

        if ($return instanceof Response) {
            return $return;
        }

        return $filterChain->doPreFilter($request, $response);
    }

    /**
     * Exception handle filter method
     *
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @throws Exception
     * @return Response|null
     */
    public function catchException(Exception $e, Request $request, Response $response, Chain $filterChain)
    {
        throw $e;
    }

    /**
     * Post filter method.
     * This should be redefined in post filter.
     *
     * For process the rest of filter chain this code should be call inside:
     * <code>
     * $filterChain->doPostFilter ();
     * </code>
     *
     * @param Request $request
     * @param Response $response
     * @param Chain $filterChain
     * @param bool $flag
     * @return Response|void
     */
    public function doPostFilter(Request $request, Response $response, Chain $filterChain, $flag = true)
    {
        return $filterChain->doPostFilter($request, $response, $flag);
    }


    public function __toString()
    {
        return (string)get_class($this);
    }

    /**
     * Logs the method call or the executed SQL statement.
     *
     * @param string $msg Message to log.
     */
    protected function log($msg)
    {
        if ($msg && $this->logger) {
            $backtrace = debug_backtrace();


            $i = 1;
            $stackSize = count($backtrace);
            do {
                $context = [];
                $callingMethod = isset($backtrace[$i]['function']) ? $backtrace[$i]['function'] : '';

                if (isset($backtrace[$i]['class'])) {
                    $context = $backtrace[$i]['class'] .'@'.$callingMethod;
                } else {
                    $context = $callingMethod;
                }

                $i++;
            } while ($callingMethod == "log" && $i < $stackSize);

            $this->logger->info($msg, ['filter' => $context]);
        }
    }
}
