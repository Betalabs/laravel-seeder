<?php

namespace Eighty8\LaravelSeeder\Migration;

use Illuminate\Database\Migrations\MigrationCreator;
use InvalidArgumentException;

class SeederMigrationCreator extends MigrationCreator
{
    const STUB_PATH = __DIR__.'/../../../stubs';
    const STUB_FILE = 'MigratableSeeder.stub';

    /**
     * Create a new seeder at the given path.
     *
     * @param string $name
     * @param string $path
     * @param string $table
     * @param bool   $create
     *
     * @throws \Exception
     *
     * @return string
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $this->ensureMigrationDoesntAlreadyExist($name);
        $this->ensurePathExists($path);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $stub = $this->getStub($table, $create);

        $this->files->put(
            $this->populateStub($stub, $table)
        );

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks($table);

        return $path;
    }

    /**
     * Ensure that a migration with the given name doesn't already exist.
     *
     * @param string $name
     * @param string $migrationPath
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected function ensureMigrationDoesntAlreadyExist($name,  $migrationPath = NULL): void
    {
        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("{$className} already exists.");
        }
    }

    /**
     * Get the migration stub file.
     *
     * @param string $table
     * @param bool   $create
     *
     * @return string
     */
    protected function getStub($table, $create): string
    {
        return $this->files->get($this->stubPath().DIRECTORY_SEPARATOR.self::STUB_FILE);
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath(): string
    {
        return self::STUB_PATH;
    }

    /**
     * Get the full path to the migration.
     *
     * @param string $name
     * @param string $path
     *
     * @return string
     */
    protected function getPath($name, $path): string
    {
        return $path.DIRECTORY_SEPARATOR.$this->getDatePrefix().'_'.$this->getClassName($name).'.php';
    }

    /**
     * Ensures the given path exists.
     *
     * @param $path
     */
    protected function ensurePathExists($path): void
    {
        if (!$this->files->exists($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }
}
