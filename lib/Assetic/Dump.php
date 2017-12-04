<?php
namespace project5\Assetic;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use project5\Template\ILoader;
use project5\Template\ITemplater;
use project5\Template\Render;
use project5\Template\Templater\Twig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatter;

class Dump extends Command
{
    protected $factory;

    protected $render;
    
    public function __construct(Factory $factory, Render $render = null)
    {
        $this->factory = $factory;

        if ($worker = $this->factory->getWorker()) {
            $worker->setIgnoreFile(true);
            $worker->setChmod(0777);
        }

        $this->render = $render;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        //$this->ignoreValidationErrors();

        $this
            ->setName('dump-assets')
            ->addOption('copy-dir',        null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'List of asset dirs just to copy', array())

            ->setDescription('Dump all used web assets')
            ->setHelp('')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $render_loader = new Twig\Loader($this->render);

        // find all twig envs!
        foreach($this->render->getRenders() as $items) {
            list($namespace, $templater, $loader) = $items; /** @var $templater ITemplater */ /* @var $loader ILoader */

            if ($templater instanceof Twig) {


                foreach($loader->getNames() as $n) {
                    $env = clone $templater->getEnvironment();
                    $env->setLoader($render_loader);

                    $name = ($namespace ? $namespace.'@':''). $n;

                    try {
                        $env->compileSource($loader->getSource($n)->getContents());


                        $formatter = $this->getHelper('formatter');
                        $output->writeln($formatter->formatSection($name, 'Parsed'));

                    } catch (\Twig_Error_Syntax $e) {
                        $formatter = $this->getHelper('formatter');
                        $output->writeln($formatter->formatSection($name, $e->getMessage(), 'error'));
                    } catch (\Twig_Error_Loader $e) {
                        $formatter = $this->getHelper('formatter');
                        $output->writeln($formatter->formatSection($name, $e->getMessage(), 'error'));
                    }
                }
            }
        }

        if ($input->hasOption('copy-dir')) {

            $process = function (AssetInterface $asset) use($output) {
                $this->factory->getWorker()->process($asset, $this->factory);

                $formatter = $this->getHelper('formatter');
                $output->writeln($formatter->formatSection('copy', $asset->getSourcePath()));
            };

            foreach($input->getOption('copy-dir') as $dir) {

                $factory = new AssetFactory($this->factory->getRoot(), false);

                $asset = $factory->createAsset($dir . '/*', [], ['output' => $dir . '/*', 'debug' => false]);
                

                $asset_list = $asset->all();
                array_walk($asset_list, function(AssetInterface $asset) use($process) {

                    $asset_list = $asset->all();
                    array_walk($asset_list, function(AssetInterface $asset) use($process) {
                        $asset->setTargetPath($asset->getSourcePath());

                        $process($asset);
                    });
                });
            }
        }

    }
}