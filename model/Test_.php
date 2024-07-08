<?php

class Test_
{
    private $db;

    public function __construct()
    {
        $this->db = new Db();
    }

    /**
     * Executes the migration for a specific table with a given schema.
     *
     * @param string $table Name of the table to migrate.
     * @param array $schema Schema definition for the table.
     * @return bool True if migration is successful, false otherwise.
     */
    public function refresh($table, $schema)
    {
        
        $model = new Refresh($table);
        $model->schema = $schema;
        return $model->refresh();
    }

    /**
     * Drops a specified table from the database.
     *
     * @param string $table Name of the table to drop.
     * @return bool True if table is dropped successfully, false otherwise.
     */
    public function dropMigration($table)
    {
        $model = new Refresh($table);
        return $model->dropTable();
    }
}
?>