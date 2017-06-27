<?php

namespace Eighty8\LaravelSeeder\Command;

use Eighty8\LaravelSeeder\Migration\SeederMigrator;
use File;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;

class SeederRollback extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seeder:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database seeding';

    /**
     * SeederMigrator.
     *
     * @var SeederMigrator
     */
    private $migrator;

    /**
     * Constructor.
     *
     * @param SeederMigrator $migrator
     */
    public function __construct(SeederMigrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     */
    public function fire(): void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        $env = $this->option('env');
        $pretend = $this->input->getOption('pretend');

        $this->migrator->setConnection($this->input->getOption('database'));

        if (File::exists(database_path(config('seeders.dir')))) {
            $this->migrator->setEnvironment($env);
        }

        $this->migrator->rollback($pretend);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['env', null, InputOption::VALUE_OPTIONAL, 'The environment in which to run the seeds.', null],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
