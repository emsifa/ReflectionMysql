<?php namespace Emsifa\ReflectionMysql;

use mysqli;

class ReflectionMysql {

    protected $connection;

    protected $dbname;

    protected $host;

    public function __construct($host, $username, $password, $dbname)
    {
        $connection = new mysqli($host, $username, $password, $dbname);

        if($connection->connect_errno) {
            throw new ConnectionFailedException("[{$connection->connect_errno}] {$connection->connect_error}", $connection->connect_errno);
        }

        $this->connection = $connection;
        $this->dbname = $dbname;
        $this->host = $host;
    }

    /**
     * Get database name
     *
     * @return string
     */
    public function getName()
    {
        return $this->dbname;
    }

    /**
     * Get mysqli connection
     *
     * @return mysqli
     */
    public function getConnection()
    {
        return $this->connection;   
    }

    /**
     * Get list tables in database
     * reference: http://stackoverflow.com/questions/8334493/get-table-names-using-select-statement-in-mysql
     *
     * @return array of Emsifa\ReflectionMysql\ReflectionTable
     */
    public function getTables()
    {
        $result = $this->connection->query("select table_name from information_schema.tables where table_schema='{$this->dbname}'");
        $tables = [];
        while($row = $result->fetch_assoc()) {
            $tables[] = $this->makeTableReflection($row['table_name']);
        }

        return $tables;
    }

    /**
     * Get table reflection
     *
     * @return null | Emsifa\ReflectionMysql\ReflectionTable
     */
    public function getTable($table)
    {
        if(!$this->hasTable($table)) return null;
        return $this->makeTableReflection($table);
    }

    /**
     * Check if table exists
     * reference: http://stackoverflow.com/questions/1525784/mysql-check-if-a-table-exists-without-throwing-an-exception
     *
     * @param string $table
     * @return boolean
     */
    public function hasTable($table)
    {
        $result = $this->connection->query("SHOW TABLES LIKE '{$table}'");
        return ($result->num_rows > 0);
    }

    /**
     * Make table reflection
     *
     * @return Emsifa\ReflectionMysql\ReflectionTable
     */
    protected function makeTableReflection($table_name)
    {
        return new ReflectionTable($this, $table_name);
    }

}