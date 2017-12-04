<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 01.01.15
 * Time: 15:16
 */
namespace project5\Cli\Command;

use project5\Application;
use project5\DI\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Expression\Glob;
use Symfony\Component\Finder\Expression\Regex;

/**
 * HelpCommand displays the help for a given command.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Config extends Command
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        parent::__construct('dump-config');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('dump-config')
            ->setAliases(['dump', 'config'])
            ->setDefinition(array(
                new InputArgument('glob', InputArgument::OPTIONAL, 'Property name or glob mask', '*'),
                new InputArgument('regex', InputArgument::OPTIONAL, 'Property name or regex', '.*'),
                new InputOption('--services', '-s', InputOption::VALUE_NONE, 'Scan services too'),
            ))
            ->setDescription('Dump app parameters')
            ->setHelp('')
        ;
    }



    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $glob = new Glob($input->getArgument('glob'));
        $regex = new Regex($input->getArgument('regex'));

        $pattern1 = (string)$glob->toRegex();
        $pattern2 = (string)$regex;

        foreach($this->container->getParameterBag()->all() as $name => $value) {
            if (preg_match($pattern1, $name) && preg_match($pattern2, $name)) {

                if (is_object($value)) {
                    $output->writeln(sprintf('%s = %s', $name, '['.get_class($value).']'));
                } else {
                    $output->writeln(sprintf('%s = %s', $name, var_export($value, true)));
                }
            } elseif ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln('Filter out "'.$name.'" property');
            }
        }

        if ($input->hasParameterOption(['--services', '-s'])) {
            foreach ($this->container->getServiceIds() as $id) {


                if (preg_match($pattern1, $id) && preg_match($pattern2, $id)) {
                    $service = $this->container->get($id);
                    $output->writeln(sprintf('%s = %s', $id, '['.get_class($service).']'));
                } elseif ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln('Filter out "' . $id . '" service');
                }
            }
        } elseif ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('Filter out services. Use -s flag to scan');
        }
    }
}
