<?php
namespace project5\Nls\I18n\Gettext;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MergeCommand  extends Command
{
    public $dir;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('merge-po')
            ->setAliases(['mergepo'])
            ->setDefinition([
                new InputOption('all','l', InputOption::VALUE_NONE, 'Source pot files dir merge from'),
                new InputArgument('file', InputArgument::REQUIRED, 'Dest po file merge to'),
                new InputArgument('dir', InputArgument::OPTIONAL, 'Source pot files dir merge from'),

            ])
            ->setDescription('Merge generated pot files')
            ->setHelp('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $po_file = $input->getArgument('file');
        $pot_dir = $input->getArgument('dir');
        $all = $input->getOption('all');

        if (!$pot_dir) $pot_dir = $this->dir;

        $base_translations = \Gettext\Translations::fromPoFile($po_file);
        $base_lang = $base_translations->getLanguage();


        // seek for pot files
        foreach(glob(rtrim($pot_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.pot') as $pot_filename)
        {


            $translations = \Gettext\Translations::fromPoFile($pot_filename);
            $lang = $translations->getLanguage();

            if ($all || $lang === $base_lang) {
                $before = $base_translations->count();
                $base_translations->mergeWith($translations);
                $after = $base_translations->count();

                $output->writeln(sprintf('Found %s pot file with %s lang (%d -> %d)', $pot_filename, $lang, $before, $after));
            } else {
                $output->writeln(sprintf('Ignore %s pot file with %s lang', $pot_filename, $lang));
            }
        }

        if (is_writable(dirname($po_file))) {
            $base_translations->toPoFile($po_file);
        }
    }
}