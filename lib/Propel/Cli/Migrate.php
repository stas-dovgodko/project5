<?php
namespace project5\Propel\Cli;

use Propel\Common\Config\ConfigurationManager;
use Propel\Generator\Command\MigrationDiffCommand;
use Propel\Generator\Command\MigrationMigrateCommand;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\MigrationManager;
use Propel\Generator\Model\Database;
use Propel\Generator\Model\Diff\DatabaseComparator;
use Propel\Generator\Model\IdMethod;
use Propel\Generator\Model\Schema;
use Propel\Generator\Util\SqlParser;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends MigrationMigrateCommand
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('schema-dir',         null, InputOption::VALUE_REQUIRED,  'The directory where the schema files are placed')
            ->addOption('table-renaming',     null, InputOption::VALUE_NONE,      'Detect table renaming', null)
            ->addOption('editor',             null, InputOption::VALUE_OPTIONAL,  'The text editor to use to open diff files', null)
            ->addOption('skip-removed-table', null, InputOption::VALUE_NONE,      'Option to skip removed table from the migration')
            ->addOption('skip-tables',        null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'List of excluded tables', array())
            ->addOption('disable-identifier-quoting', null, InputOption::VALUE_NONE, 'Disable identifier quoting in SQL queries for reversed database tables.')
            ->addOption('comment',            "m",  InputOption::VALUE_OPTIONAL,  'A comment for the migration', '')
        ;
    }

    protected function migrateMakeDiff(GeneratorConfig $generatorConfig, MigrationManager $manager, InputInterface $input, OutputInterface $output)
    {
        $totalNbTables = 0;
        $reversedSchema = new Schema();

        $connections = array();
        $optionConnections = $input->getOption('connection');
        if (!$optionConnections) {
            $connections = $generatorConfig->getBuildConnections();
        } else {
            foreach ($optionConnections as $connection) {
                list($name, $dsn, $infos) = $this->parseConnection($connection);
                $connections[$name] = array_merge(array('dsn' => $dsn), $infos);
            }
        }

        foreach ($manager->getDatabases() as $appDatabase) {

            $name = $appDatabase->getName();
            if (!$params = @$connections[$name]) {
                $output->writeln(sprintf('<info>No connection configured for database "%s"</info>', $name));
            }

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Connecting to database "%s" using DSN "%s"', $name, $params['dsn']));
            }

            $conn     = $manager->getAdapterConnection($name);
            $platform = $generatorConfig->getConfiguredPlatform($conn, $name);

            if (!$platform->supportsMigrations()) {
                $output->writeln(sprintf('Skipping database "%s" since vendor "%s" does not support migrations', $name, $platform->getDatabaseType()));
                continue;
            }

            $additionalTables = [];
            foreach ($appDatabase->getTables() as $table) {
                if ($table->getSchema() && $table->getSchema() != $appDatabase->getSchema()) {
                    $additionalTables[] = $table;
                }
            }

            if ($input->getOption('disable-identifier-quoting')) {
                $platform->setIdentifierQuoting(false);
            }

            $database = new Database($name);
            $database->setPlatform($platform);
            $database->setSchema($appDatabase->getSchema());
            $database->setDefaultIdMethod(IdMethod::NATIVE);

            $parser   = $generatorConfig->getConfiguredSchemaParser($conn, $name);
            $nbTables = $parser->parse($database, $additionalTables);

            $reversedSchema->addDatabase($database);
            $totalNbTables += $nbTables;

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('%d tables found in database "%s"', $nbTables, $name), Output::VERBOSITY_VERBOSE);
            }
        }

        if ($totalNbTables) {
            $output->writeln(sprintf('%d tables found in all databases.', $totalNbTables));
        } else {
            $output->writeln('No table found in all databases');
        }

        // comparing models
        $output->writeln('Comparing models...');
        $tableRenaming = $input->getOption('table-renaming');

        $migrationsUp   = array();
        $migrationsDown = array();
        $removeTable = !$input->getOption('skip-removed-table');
        $excludedTables = $input->getOption('skip-tables');
        foreach ($reversedSchema->getDatabases() as $database) {
            $name = $database->getName();

            if ($input->getOption('verbose')) {
                $output->writeln(sprintf('Comparing database "%s"', $name));
            }

            if (!$appDataDatabase = $manager->getDatabase($name)) {
                $output->writeln(sprintf('<error>Database "%s" does not exist in schema.xml. Skipped.</error>', $name));
                continue;
            }

            $configManager = new ConfigurationManager();
            $excludedTables = array_merge((array) $excludedTables, (array) $configManager->getSection('exclude_tables'));

            $databaseDiff = DatabaseComparator::computeDiff($database, $appDataDatabase, false, $tableRenaming, $removeTable, $excludedTables);

            if (!$databaseDiff) {
                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf('Same XML and database structures for datasource "%s" - no diff to generate', $name));
                }
                continue;
            }

            $output->writeln(sprintf('Structure of database was modified in datasource "%s": %s', $name, $databaseDiff->getDescription()));

            foreach ($databaseDiff->getPossibleRenamedTables() as $fromTableName => $toTableName) {
                $output->writeln(sprintf(
                    '<info>Possible table renaming detected: "%s" to "%s". It will be deleted and recreated. Use --table-renaming to only rename it.</info>',
                    $fromTableName, $toTableName
                ));
            }

            $conn     = $manager->getAdapterConnection($name);
            $platform = $generatorConfig->getConfiguredPlatform($conn, $name);
            if ($input->getOption('disable-identifier-quoting')) {
                $platform->setIdentifierQuoting(false);
            }
            $migrationsUp[$name]    = $platform->getModifyDatabaseDDL($databaseDiff);
            $migrationsDown[$name]  = $platform->getModifyDatabaseDDL($databaseDiff->getReverseDiff());
        }

        if (!$migrationsUp) {
            $output->writeln('Same XML and database structures for all datasource - no diff to generate');

            return;
        }

        $timestamp = time();
        $migrationFileName  = $manager->getMigrationFileName($timestamp);
        $migrationClassBody = $manager->getMigrationClassBody($migrationsUp, $migrationsDown, $timestamp, $input->getOption('comment'));

        $file = $generatorConfig->getSection('paths')['migrationDir'] . DIRECTORY_SEPARATOR . $migrationFileName;
        file_put_contents($file, $migrationClassBody);

        $output->writeln(sprintf('"%s" file successfully created.', $file));

        if (null !== $editorCmd = $input->getOption('editor')) {
            $output->writeln(sprintf('Using "%s" as text editor', $editorCmd));
            shell_exec($editorCmd . ' ' . escapeshellarg($file));
        } else {
            $output->writeln('Please review the generated SQL statements, and add data migration code if necessary.');
            $output->writeln('Once the migration class is valid, call the "migrate" task to execute it.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function migrateApplyDiff(MigrationManager $manager, InputInterface $input, OutputInterface $output)
    {
        if (!$manager->getFirstUpMigrationTimestamp()) {
            $output->writeln('All migrations were already executed - nothing to migrate.');

            return false;
        }

        $timestamps = $manager->getValidMigrationTimestamps();
        if (count($timestamps) > 1) {
            $output->writeln(sprintf('%d migrations to execute', count($timestamps)));
        }

        foreach ($timestamps as $timestamp) {

            if ($input->getOption('fake')) {
                $output->writeln(
                    sprintf('Faking migration %s up', $manager->getMigrationClassName($timestamp))
                );
            } else {
                $output->writeln(
                    sprintf('Executing migration %s up', $manager->getMigrationClassName($timestamp))
                );
            }

            if (!$input->getOption('fake')) {
                $migration = $manager->getMigrationObject($timestamp);
                if (property_exists($migration, 'comment') && $migration->comment) {
                    $output->writeln(sprintf('<info>%s</info>', $migration->comment));
                }

                if (false === $migration->preUp($manager)) {
                    if ($input->getOption('force')) {
                        $output->writeln('<error>preUp() returned false. Continue migration.</error>');
                    } else {
                        $output->writeln('<error>preUp() returned false. Aborting migration.</error>');

                        return false;
                    }
                }

                foreach ($migration->getUpSQL() as $datasource => $sql) {
                    $connection = $manager->getConnection($datasource);
                    if ($input->getOption('verbose')) {
                        $output->writeln(
                            sprintf(
                                'Connecting to database "%s" using DSN "%s"',
                                $datasource,
                                $connection['dsn']
                            )
                        );
                    }

                    $conn = $manager->getAdapterConnection($datasource);
                    $res = 0;
                    $statements = SqlParser::parseString($sql);

                    foreach ($statements as $statement) {
                        try {
                            if ($input->getOption('verbose')) {
                                $output->writeln(sprintf('Executing statement "%s"', $statement));
                            }
                            $conn->exec($statement);
                            $res++;
                        } catch (\Exception $e) {
                            if ($input->getOption('force')) {
                                //continue, but print error message
                                $output->writeln(
                                    sprintf('<error>Failed to execute SQL "%s". Continue migration.</error>', $statement)
                                );
                            } else {
                                throw new RuntimeException(
                                    sprintf('<error>Failed to execute SQL "%s". Aborting migration.</error>', $statement),
                                    0,
                                    $e
                                );
                            }
                        }
                    }

                    $output->writeln(
                        sprintf(
                            '%d of %d SQL statements executed successfully on datasource "%s"',
                            $res,
                            count($statements),
                            $datasource
                        )
                    );
                }
            }

            // migrations for datasources have passed - update the timestamp
            // for all datasources
            foreach ($manager->getConnections() as $datasource => $connection) {
                $manager->updateLatestMigrationTimestamp($datasource, $timestamp);
                if ($input->getOption('verbose')) {
                    $output->writeln(sprintf(
                        'Updated latest migration date to %d for datasource "%s"',
                        $timestamp,
                        $datasource
                    ));
                }
            }

            if (!$input->getOption('fake')) {
                $migration->postUp($manager);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configOptions = array();

        if ($this->hasInputOption('output-dir', $input)) {
            $configOptions['propel']['paths']['migrationDir'] = $input->getOption('output-dir');
        }

        if ($this->hasInputOption('migration-table', $input)) {
            $configOptions['propel']['migrations']['tableName'] = $input->getOption('migration-table');
        }

        if ($this->hasInputOption('schema-dir', $input)) {
            $configOptions['propel']['paths']['schemaDir'] = $input->getOption('schema-dir');
        }


        $generatorConfig = $this->getGeneratorConfig($configOptions, $input);

        $this->createDirectory($generatorConfig->getSection('paths')['migrationDir']);

        $manager = new MigrationManager();
        $manager->setGeneratorConfig($generatorConfig);
        $manager->setSchemas($this->getSchemas($generatorConfig->getSection('paths')['schemaDir'], $input->getOption('recursive')));


        $connections = array();
        $optionConnections = $input->getOption('connection');
        if (!$optionConnections) {
            $connections = $generatorConfig->getBuildConnections();
        } else {
            foreach ($optionConnections as $connection) {
                list($name, $dsn, $infos) = $this->parseConnection($connection);
                $connections[$name] = array_merge(array('dsn' => $dsn), $infos);
            }
        }

        $manager->setConnections($connections);
        $manager->setMigrationTable($generatorConfig->getSection('migrations')['tableName']);
        $manager->setWorkingDirectory($generatorConfig->getSection('paths')['migrationDir']);

        if ($manager->hasPendingMigrations()) {
            $this->migrateApplyDiff($manager, $input, $output);
        }

        $this->migrateMakeDiff($generatorConfig, $manager, $input, $output);

        if ($manager->hasPendingMigrations()) {

            $this->migrateApplyDiff($manager, $input, $output);

            $output->writeln('Migration complete.');
        } else {
            $output->writeln('Migration complete. No migration to execute.');
        }



    }
}