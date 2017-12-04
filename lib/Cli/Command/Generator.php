<?php
/**
 * Created by PhpStorm.
 * User: Стас
 * Date: 01.01.15
 * Time: 15:16
 */
namespace project5\Cli\Command;

use Composer\Composer;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use project5\Application;
use project5\DI\Container;
use project5\Form;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Generator extends Command
{
    public function __construct()
    {
        parent::__construct('generate');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('generate')
            ->setAliases(['gen'])
            ->addArgument('output', InputArgument::OPTIONAL, 'Dump to file', '')
            ->addOption('form', 'f', InputOption::VALUE_OPTIONAL, 'Form class', '')
            ->addOption('namespace', 'ns', InputOption::VALUE_OPTIONAL, 'Namespace', '[default]')
            ->setDescription('Generate app classes')
            ->setHelp('')
        ;
    }



    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $namespace = $input->getOption('namespace');
        if ($namespace === '[default]') $namespace = null;

        $ns = null;
        if ($form = $input->getOption('form')) {
            $ns = new PhpNamespace(($namespace !== null) ? trim($input->getOption('namespace')) : 'Form');
            $class = $ns->addClass($form);
            $class->setExtends(Form::class);

            $class->addMethod('init')->addBody('// place your fields here');

        } else {
            throw new \InvalidArgumentException('Please specify one of object type argument, form for exmaple');
        }

        if ($filename = $input->getArgument('output')) {
            if (!is_file($filename)) {
                if (is_dir(dirname($filename))) {
                    file_put_contents($filename, (string)$ns);

                    if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                        $output->writeln(sprintf("Saved src to %s file", $filename));
                    }
                } else {
                    throw new \InvalidArgumentException(sprintf('Can\'t put output to %s file, parent directory missing', $filename));
                }
            } else {
                throw new \InvalidArgumentException(sprintf('Can\'t put output to %s file, file already exists', $filename));
            }
        } else {
            $output->write((string)$ns, true, OutputInterface::OUTPUT_PLAIN);
        }
    }
}
