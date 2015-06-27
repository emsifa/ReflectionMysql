<?php namespace Emsifa\ReflectionMysql;

class ReflectionTable {

    protected $database;

    protected $name;

    protected $initialized = false;

    protected $primary;

    public function __construct(ReflectionMysql $database, $name)
    {
        $this->database = $database;
        $this->name = $name;

        $this->initialize();
    }

    public function getConnection()
    {
        return $this->getDatabase()->getConnection();
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getColumns()
    {
        return $this->columns;
    }

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

    public function getPrimary()
    {
        return $this->primary;
    }

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

    public function getColumn($colname)
    {
        if($this->hasColumn($colname)) {
            return $this->columns[$colname];
        }

        return null;   
    }

    public function hasColumn($colname)
    {
        $columns = $this->getColumns();
        return array_key_exists($colname, $columns);
    }

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