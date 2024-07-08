<?php

class Db
{
    // Database credentials
    private $host;
    private $user;
    private $pass;
    private $dbname;

    // Database handler, error handler, and statement
    private $pdo;
    private $error;
    private $stmt;

    /**
     * Constructor to initialize database connection
     */
    public function __construct()
    {
        $this->host = getenv('DB_HOST');
        $this->user = getenv('DB_USER');
        $this->pass = getenv('DB_PASS');
        $this->dbname = getenv('DB_NAME');

        // Data Source Name (DSN) specifying the database type, host, and database name
        $dsn = 'pgsql:host=' . $this->host . ';dbname=' . $this->dbname;
        // PDO options for persistent connection and error mode
        $options = [
            PDO::ATTR_PERSISTENT => true, // Persistent connection
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Throw exceptions on errors
        ];
        try {
            // Create a new PDO instance with the DSN and options
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // Catch any connection errors and store the error message
            $this->error = $e->getMessage();
            echo 'Error: ' . $this->error;
            die;
        }
    }

    /**
     * Prepare a SQL query
     * 
     * @param string $sql The SQL query to prepare
     */
    public function query($sql)
    {
        $this->stmt = $this->pdo->prepare($sql);
        return $this;
    }

    /**
     * Binds a value to a corresponding named or positional placeholder in the SQL statement.
     *
     * @param mixed $param Parameter identifier (name or position).
     * @param mixed $value Value to bind to the parameter.
     * @param int|null $type Explicit data type for the parameter.
     * @return $this
     */
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    /**
     * Executes a prepared statement.
     *
     * @return bool
     */
    public function execute()
    {
        return $this->stmt->execute();
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @return array
     */
    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Fetches the next row from a result set as an object.
     *
     * @return object
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Initiates a transaction.
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @return bool
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rolls back a transaction.
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

}
?>