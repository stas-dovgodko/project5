<?php
namespace project5\DI;

use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ParameterBag extends \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag
{
    /**
     * @var ParameterBagInterface
     */
    protected $base;

    public function __construct(ParameterBagInterface $base)
    {
        $this->base = $base;

        parent::__construct([]);
    }

    /**
     * @return ParameterBagInterface
     */
    public function getBaseParameterBag()
    {
        return $this->base;
    }

    /**
     * @param ParameterBagInterface $useParameterBag
     */
    public function setBaseParameterBag($parameterBag)
    {
        $this->base = $parameterBag;
    }

    /**
     * Gets a service container parameter.
     *
     * @param string $name The parameter name
     *
     * @return mixed The parameter value
     *
     * @throws ParameterNotFoundException if the parameter is not defined
     */
    public function get($name)
    {
        try {
            return parent::get($name);
        } catch (ParameterNotFoundException $e) {
            return $this->base->get($name);
        }
    }

    /**
     * Returns true if a parameter name is defined.
     *
     * @param string $name The parameter name
     *
     * @return bool true if the parameter name is defined, false otherwise
     */
    public function has($name)
    {
        if (!parent::has($name)) {
            return $this->base->has($name);
        } else return true;
    }

    /**
     * Gets the service container parameters.
     *
     * @return array An array of parameters
     */
    public function all()
    {
        return array_merge($this->base->all(), $this->parameters);
    }

    /**
     * Replaces parameter placeholders (%name%) by their values for all parameters.
     */
    public function resolve()
    {
        if ($this->resolved) {
            return;
        }

        $parameters = array();
        foreach ($this->all() as $key => $value) {
            try {
                $value = $this->resolveValue($value);
                $parameters[$key] = $this->unescapeValue($value);
            } catch (ParameterNotFoundException $e) {
                $e->setSourceKey($key);

                throw $e;
            }
        }

        $this->parameters = $parameters;
        $this->resolved = true;
    }
}