<?php

namespace Eighty8\LaravelSeeder\Repository;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;

class SeederRepository implements SeederRepositoryInterface
{
    /**
     * The name of the environment to run in.
     *
     * @var string
     */
    public $environment;

    /**
     * The database connection resolver instance.
     *
     * @var ConnectionResolverInterface
     */
    protected $connectionResolver;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new database migration repository instance.
     *
     * @param ConnectionResolverInterface $resolver
     * @param string                      $table
     */
    public function __construct(ConnectionResolverInterface $resolver, string $table)
    {
        $this->connectionResolver = $resolver;
        $this->table = $table;
    }

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan(): array
    {
        return $this->table()
            ->where('env', '=', $this->getEnvironment())
            ->pluck('seed')
            ->toArray();
    }

    /**
     * Get the list of migrations.
     *
     * @param int $batch
     *
     * @return array
     */
    public function getMigrationsByBatch($batch) : array
    {
        return $this->getMigrationBatches();
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return Builder
     */
    protected function table(): Builder
    {
        return $this->getConnection()->table($this->table);
    }

    /**
     * Resolve the database connection instance.
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection():\Illuminate\Database\ConnectionInterface
    {
        return $this->connectionResolver->connection($this->connection);
    }

    /**
     * Determines whether an environment has been set.
     *
     * @return bool
     */
    public function hasEnvironment(): bool
    {
        return !empty($this->getEnvironment());
    }

    /**
     * Gets the environment the seeds are ran against.
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    /**
     * Set the environment to run the seeds against.
     *
     * @param $env
     */
    public function setEnvironment(string $env): void
    {
        $this->environment = $env;
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast(): array
    {
        return $this->table()
            ->where('env', '=', $this->getEnvironment())
            ->where('batch', $this->getLastBatchNumber())
            ->orderBy('seed', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    protected function getLastBatchNumber(): int
    {
        $max = $this->table()
            ->where('env', '=', $this->getEnvironment())
            ->max('batch');

        return ($max) ?: 0;
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param int    $batch
     *
     * @return void
     */
    public function log($file, $batch): void
    {
        $this->table()->insert([
            'seed'  => $file,
            'env'   => $this->getEnvironment(),
            'batch' => $batch,
        ]);
    }

    /**
     * Remove a migration from the log.
     *
     * @param $seeder
     */
    public function delete($seeder): void
    {
        $this->table()
            ->where('env', '=', $this->getEnvironment())
            ->where('seed', $seeder->seed)
            ->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository(): void
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function (Blueprint $table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            $table->string('seed');
            $table->string('env');
            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Set the information source to gather data.
     *
     * @param string $name
     */
    public function setSource($name): void
    {
        $this->connection = $name;
    }

    /**
     * Get list of migrations.
     *
     * @param int $steps
     *
     * @return array
     */
    public function getMigrations($steps): array
    {
        return $this->table()->get()->toArray();
    }
    
    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return array
     */
    public function getMigrationBatches(): array
    {
        return $this->table()
                ->orderBy('batch', 'asc')
                ->orderBy('migration', 'asc')
                ->pluck('batch', 'migration')->all();
    }
    
    /**
     * Delete the migration repository data store.
     *
     * @return void
     */
    public function deleteRepository() {
        //   
    }
}
