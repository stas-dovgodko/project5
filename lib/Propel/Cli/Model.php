<?php
namespace project5\Propel\Cli;

use Propel\Generator\Command\ModelBuildCommand;

class Model extends  ModelBuildCommand
{
    use Command;

    /**
     * Constructor.
     *
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     *
     * @throws \LogicException When the command name is empty
     *
     * @api
     */
    public function __construct($connections = [], $tmp = null)
    {
        parent::__construct(null);

        $this->connections = $connections;
        $this->tmp = $tmp;
    }
}