<?php

class Refresh extends Db
{
    private $tableName;
    public $schema;

    public function __construct($tableName)
    {
        parent::__construct(); // Call the parent constructor to establish the database connection
        $this->tableName = $tableName;
    }

    /**
     * Perform migration to create a new table based on defined schema.
     *
     * @return bool True if migration is successful, false otherwise.
     */
    public function refresh()
    {
        // Check if schema is defined
        if (empty($this->schema)) {
            Response::set([
                'statusCode' => 500,
                'message' => 'Schema is not defined'
            ]);
            return false;
        }

        // Drop table if it exists
        $dropTableSQL = "DROP TABLE IF EXISTS {$this->tableName}";

        // Construct column definitions from schema
        $columns = [];
        foreach ($this->schema as $column => $type) {
            $columns[] = "$column $type";
        }
        $columnsSql = implode(', ', $columns);
        $sql = "CREATE TABLE {$this->tableName} ($columnsSql)";

        try {
            // Execute drop table
            $this->query($dropTableSQL);
            $this->execute();

            // Execute create table
            $this->query($sql);
            $this->execute();

            return true;
        } catch (Exception $e) {
            Response::set([
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Drop the specified table.
     *
     * @return bool True if table is dropped successfully, false otherwise.
     */
    public function dropTable()
    {
        // Drop table SQL statement
        $dropTableSQL = "DROP TABLE {$this->tableName}";

        try {
            // Execute drop table
            $this->query($dropTableSQL);
            $this->execute();

            return true;
        } catch (Exception $e) {
            Response::set([
                'statusCode' => 500,
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }
}

?>
