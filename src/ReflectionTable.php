<?php namespace Emsifa\ReflectionMysql;

class ReflectionTable {

    /**
     * @var Emsifa\ReflectionMysql\ReflectionMysql
     */
    protected $database;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var boolean
     */
    protected $initialized = false;

    /**
     * @var null | Emsifa\ReflectionMysql\ReflectionColumn
     */
    protected $primary;

    public function __construct(ReflectionMysql $database, $name)
    {
        $this->database = $database;
        $this->name = $name;

        $this->initialize();
    }

    /**
     * Get mysqli connection
     *
     * @return mysqli
     */
    public function getConnection()
    {
        return $this->getDatabase()->getConnection();
    }

    /**
     * Get database reflection
     *
     * @return Emsifa\ReflectionMysql\ReflectionMysql
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get list columns
     *
     * @return array of Emsifa\ReflectionMysql\ReflectionColumn
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get indexed columns
     *
     * @return array of Emsifa\ReflectionMysql\ReflectionColumn
     */
    public function getIndexes()
    {
        $columns = $this->getColumns();
        $indexes = [];
        foreach($columns as $colname => $column) {
            if($column->isIndexed()) {
                $indexes[$colname] = $column;
            }
        }

        return $indexes;
    }

    /**
     * Get indexed columns
     *
     * @return null | Emsifa\ReflectionMysql\ReflectionColumn
     */
    public function getPrimary()
    {
        return $this->primary;
    }

    /**
     * Get related columns
     *
     * @return array of Emsifa\ReflectionMysql\ReflectionColumn
     */
    public function getRelations()
    {
        $columns = $this->getColumns();
        $relations = [];
        foreach($columns as $colname => $column) {
            $col_relations = $column->getRelations();
            $colkey = $this->name.'.'.$colname;
            foreach($col_relations as $refkey => $col) {
                $relations[$colkey.':'.$refkey] = [$column, $col];
            }
        }

        return $relations;
    }

    /**
     * Get specified column
     *
     * @return null | Emsifa\ReflectionMysql\ReflectionColumn
     */
    public function getColumn($colname)
    {
        if($this->hasColumn($colname)) {
            return $this->columns[$colname];
        }

        return null;   
    }

    /**
     * Check if table has column
     *
     * @return boolean
     */
    public function hasColumn($colname)
    {
        $columns = $this->getColumns();
        return array_key_exists($colname, $columns);
    }

    /**
     * Initialize table
     *
     * @return void
     */
    protected function initialize()
    {
        $connection = $this->getConnection();
        $column_names = $this->fetchColumnNames();

        $columns = [];
        foreach($column_names as $colname) {
            $column = new ReflectionColumn($this, $colname);

            if($column->isPrimary()) {
                $this->primary = $column;
            }

            $columns[$colname] = $column;
        }


        $this->columns = $columns;
    }

    /**
     * Fetch column names in table
     *
     * @return array
     */
    protected function fetchColumnNames()
    {
        $connection = $this->getConnection();
        $dbname = $this->getDatabase()->getName();
        $tablename = $this->getName();

        $query = "
            SELECT `COLUMN_NAME` 
            FROM `information_schema`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = '{$dbname}'
                AND `TABLE_NAME` = '{$tablename}'
            ORDER BY `ORDINAL_POSITION`
        ";

        $result = $connection->query($query);

        if($connection->error) throw new QueryErrorException($connection->error); 
        
        $column_names = [];
        while($row = $result->fetch_assoc()) {
            $column_names[] = $row['COLUMN_NAME'];
        }

        return $column_names;
    }

}