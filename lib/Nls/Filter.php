<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 03.01.15
 * Time: 16:04
 */
namespace project5\Nls;

use project5\Web\Filter as AbstractFilter;
use project5\Web\Filter\Chain;
use project5\Web\Request;
use project5\Web\Response;

class Filter extends AbstractFilter
{
    const SESSION_NAMESPACE = 'nls';

    /**
     * @var LocaleManager
     */
    private $manager;

    /**
     * @var LocaleManager
     */
    private $fallbackManager;

    public function __construct(LocaleManager $manager, array $fallbackDetectors = [])
    {
        $this->manager = $manager;

        $this->fallbackManager = new LocaleManager($manager->getSupported(), $fallbackDetectors);
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
     * @return bool
     */
    public function doPreFilter(Request $request, Response $response, Chain $filterChain)
    {
        $locale = $this->manager->getLocale();



        $session = $request->getSession();
        if ($locale) {
            $session->set(self::SESSION_NAMESPACE, 'locale', $locale);
        } else {
            $locale = $session->get(self::SESSION_NAMESPACE, 'locale', $this->fallbackManager->getLocale());
        }

        if ($locale) {
            $this->manager->setLocale($locale);
        }

        return $filterChain->doPreFilter($request, $response);
    }
}