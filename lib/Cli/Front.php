<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 01.01.15
 * Time: 15:16
 */
namespace project5\Cli;

use project5\Application;
use project5\DI\Container;
use project5\DI\IContainer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;


class Front extends SymfonyConsoleApplication implements IContainer
{
    /**
     * @var callable
     */
    private $configure;

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var Output
     */
    protected $output;

    public function __construct(callable $configure = null, Input $input, Output $output = null)
    {
        parent::__construct('project5');

        $this->input = $input;

        $this->output = $output ? $output : new ConsoleOutput();

        $this->configure = $configure;
    }

    protected function getDefaultInputDefinition()
    {
        $definition = new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help', '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--quiet', '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--version', '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi', '', InputOption::VALUE_NONE, 'Force ANSI output'),
            new InputOption('--no-ansi', '', InputOption::VALUE_NONE, 'Disable ANSI output'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ));



        return $definition;
    }

    /**
     * @param callable $configure
     */
    public function setConfigure($configure)
    {
        $this->configure = $configure;
    }


    /**
     * @return Input
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param Input $input
     */
    public function setInput($input)
    {
        $this->input = $input;
    }

    /**
     * @return Output
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Output $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function handle(Application $app)
    {
        if ($env = $app->isConfigured()) {
            if (is_string($env)) {
                $this->setVersion($env);
                //$this->output->writeln(sprintf('Env - %s', $env));
            }
            $this->run($this->input, $this->output);
        } else {
            throw new \DomainException('App not configured yet!');
        }

    }

    public static function CreateInput()
    {
        return new ArgvInput($_SERVER['argv']);
    }

    /*
    public static function Factory($tool_name, $info, ContainerBuilder $container)
    {

        if (isset($info['command'])) {
            $class = $info['command'];

        if(0) {
            if (isset($info['arguments'])) {
                $args = [];
                // Max priority
                foreach ($info['arguments'] as $name => $value) {

                    if ($value instanceof Reference) {
                        $value = $container->get($value);
                    }

                    $args[] = $value;
                }
            } else {
                $args = [$tool_name];
            }}

            $r = new \ReflectionClass($class);
            $command = $r->newInstanceArgs($args);



            if (isset($info['options'])) {
                // Max priority
                foreach ($info['options'] as $name => $value) {

                    if ($value instanceof Reference) {
                        $value = $container->get($value);
                    }


                    if ($command->getDefinition()->hasArgument($name)) {
                        $command->getDefinition()->getArgument($name)->setDefault($value);
                    } elseif ($command->getDefinition()->hasOption($name)) {
                        $command->getDefinition()->getOption($name)->setDefault($value);
                    }
                }
            }
            if ($command instanceof Command) {
                $alias = $command->getName();
                $command->setName($tool_name);
                $command->setAliases($command->getAliases() + [$alias]);
            } else {
                throw new \DomainException('Symfony command class expected for "'.$tool_name.'" tool');
            }
        } else {
            $command = new Command($tool_name);

            if (isset($info['method'])) {
                $command->setCode(function (InputInterface $input, OutputInterface $output) use ($container, $tool_name, $info) {
                    $args = array();
                    if (isset($info['properties'])) {
                        // Max priority
                        foreach ($info['properties'] as $name => $value) {
                            if ($value instanceof Reference) {
                                $value = $container->get($value);
                            }
                            $args[$name] = $value;
                        }
                    }
                    if (isset($info['required'])) {
                        foreach ($info['required'] as $name => $desc) {
                            if (!isset($args[$name])) {
                                $args[$name] = $input->getArgument($name);
                            } else {
                                $arguments = $input->getArguments();
                                if (!empty($arguments[$name])) {
                                    $args[$name] = $input->getArgument($name);
                                }
                            }
                        }
                    }
                    if (isset($info['optional'])) {
                        foreach ($info['optional'] as $name => $desc) {
                            if (!isset($args[$name]) && $input->hasArgument($name)) {
                                $args[$name] = $input->getArgument($name);
                            }
                        }
                    }

                    if (is_array($info['method'])) {
                        list($class, $method) = $info['method'];
                        $reflection = (new \ReflectionClass($class))->getMethod($method);
                    } else {
                        $reflection = new \ReflectionFunction($info['method']);
                    }


                    $parameters = [];
                    foreach ($reflection->getParameters() as $param) {
                        $param_name = $param->getName();
                        $class = ($class = $param->getClass()) ? $class->getName() : '';
                        switch ($class) {
                            case InputInterface::CLASS:
                                $parameters[$param_name] = $input;
                                break;
                            case OutputInterface::CLASS:
                                $parameters[$param_name] = $output;
                                break;
                            default:
                                if (isset($args[$param_name = $param->getName()])) {
                                    $parameters[$param_name] = $args[$param_name];
                                } else {
                                    throw new \InvalidArgumentException("Can't found \"$param_name\" argument for tool");
                                }
                        }
                    }

                    if ($reflection instanceof \ReflectionMethod) {
                        $reflection->invokeArgs(null, $parameters);
                    } else {
                        $reflection->invokeArgs($parameters);
                    }
                });
            }
        }


        return $command;
    }*/

    public static function Setup(Container $container, $id)
    {

    }

    public static function Inject(Definition $definition, $config, ContainerBuilder $builder, $setupOptions = null)
    {
        foreach ($config as $tool_name => $info) {

            if (isset($info['command'])) {
                $command_def = new Definition($info['command'], isset($info['arguments']) ? $info['arguments'] : []);
            } elseif(isset($info['method'])) {
                $command_def = new Definition(Command::class);
                $command_def->setArguments([$tool_name, $info['method']]);
            } else {
                throw new \DomainException('cli.tools should contains command or method section');
            }
            


            //$command_def->setLazy(true);

            if (isset($info['properties'])) {
                // Max priority
                $command_def->setProperties($info['properties']);
            }

            if (isset($info['description'])) {
                $command_def->addMethodCall('setDescription', [$info['description']]);
            }

            if (isset($info['aliases']) && is_array($info['aliases'])) {
                $command_def->addMethodCall('setAliases', [$info['aliases']]);
            }

            if (isset($info['required'])) {
                foreach ($info['required'] as $name => $description) {

                    $command_def->addMethodCall('addArgument', [$name, InputOption::VALUE_REQUIRED, $description]);
                }
            }

            if (isset($info['options'])) {

                $options = $info['options'];
                $command_def->setConfigurator(function(SymfonyCommand $command) use($builder, $options) {
                    // Max priority
                    foreach ($options as $name => $value) {

                        if ($value instanceof Reference) {
                            $value = $builder->get($value);
                        }


                        if ($command->getDefinition()->hasArgument($name)) {
                            $command->getDefinition()->getArgument($name)->setDefault($value);
                        } elseif ($command->getDefinition()->hasOption($name)) {
                            $command->getDefinition()->getOption($name)->setDefault($value);
                        }
                    }
                });
            }

            $builder->setDefinition(
                $tool_name,
                $command_def->addTag('console.command')
            );
            //$front->add($command);
        }
    }
}