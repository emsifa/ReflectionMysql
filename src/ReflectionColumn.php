<?php namespace Emsifa\ReflectionMysql;

class ReflectionColumn {

    protected $table;

    protected $name;

    protected $informations = array();

    protected $has_initialize_relations = false;

    public function __construct(ReflectionTable $table, $colname) {
        $this->table = $table;
        $this->name = $colname;
        $this->informations = $this->fetchColumnInformations();
    }

    public function getConnection()
    {
        return $this->getTable()->getConnection();
    }


    public function getDatabase()
    {
        return $this->getTable()->getDatabase();
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getInfo($key, $default = null)
    {
        $key = strtoupper($key);
        return array_key_exists($key, $this->informations)? $this->informations[$key] : $default;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->getInfo('DATA_TYPE');
    }

    public function getDefault()
    {
        return $this->getInfo('COLUMN_DEFAULT');
    }

    public function isNullable()
    {
        return ($this->getInfo('IS_NULLABLE') == 'YES');
    }

    public function getComment()
    {
        return $this->getInfo('COLUMN_COMMENT');
    }

    public function getLength()
    {
        $length = $this->getInfo('CHARACTER_MAXIMUM_LENGTH');
        return is_numeric($length)? $length : null;
    }

    public function getCharset()
    {
        return $this->getInfo('CHARACTER_SET_NAME');
    }

    public function getCollation()
    {
        return $this->getInfo('COLLATION_NAME');
    }

    public function isAutoIncrement()
    {
        return $this->getInfo('EXTRA') == 'auto increment';
    }

    public function getCatalog()
    {
        return $this->getInfo('TABLE_CATALOG');
    }

    public function getSortOrder()
    {
        return $this->getInfo('ORDINAL_POSITION');
    }

    public function isPrimary()
    {
        return (strtoupper($this->getInfo('COLUMN_KEY')) == 'PRI');
    }

    public function isUnique($include_primary = false)
    {
        return (strtoupper($this->getInfo('COLUMN_KEY')) == 'UNI');
    }

    public function isIndex($or_unique_or_primary)
    {   
        return (strtoupper($this->getInfo('COLUMN_KEY')) == 'MUL');
    }

    public function isIndexed()
    {
        return ($this->isPrimary() OR $this->isIndex() OR $this->isUnique());
    }

    public function getRelations()
    {
        if(!$this->has_initialize_relations) {
            $this->initializeColumnRelations();
        }

        $relations = [];
        $database = $this->getDatabase();

        foreach($this->relations as $key => $data) {
            // $data is array relation data that fetched from fetchColumnRelations()
            $table = $database->getTable($data['tablename']);
            $column = $table->getColumn($data['colname']);

            $relations[$key] = $column;
        }

        return $relations;
    }

    public function isRelatedWith($table, $column_name = null)
    {
        $relations = $this->getRelations();

        if($table instanceof ReflectionTable) {
            
            foreach($relations as $colname => $column) {
                if($table->getName() == $column->getTable()->getName()) {
                    return TRUE;
                }
            }

            return FALSE;

        } elseif($table instanceof ReflectionColumn) {
            
            $coltable = $table->getTable();
            $col = $table;

            foreach($relations as $colname => $column) {
                $table = $column->getTable();
                if($coltable->getName() == $table->getName() AND $colname == $col->getName()) {
                    return TRUE;
                }
            }
            
            return FALSE;
        
        } elseif(is_string($table) AND !is_string($column_name)) {
        
            foreach($relations as $colname => $column) {
                $table_name = $column->getTable()->getName();
                if($table_name == $table) {
                    return TRUE;
                }
            }
            
            return FALSE;

        } elseif(is_string($table) AND is_string($column_name)) {

            foreach($relations as $colname => $column) {
                $table_name = $column->getTable()->getName();
                if($table_name == $table AND $colname == $column_name) {
                    return TRUE;
                }
            }
            
            return FALSE;

        }

        return FALSE;
    }

    protected function fetchColumnInformations()
    {
        $tablename = $this->table->getName();
        $connection = $this->getConnection();
        $colname = $this->getName();
        $dbname = $this->getDatabase()->getName();

        $query = "
            SELECT * 
            FROM `information_schema`.`COLUMNS` as `Col`
            LEFT JOIN `information_schema`.`KEY_COLUMN_USAGE` as `Key` on `Col`.`COLUMN_NAME` = `Key`.`COLUMN_NAME` 
            WHERE Col.`TABLE_SCHEMA` = '{$dbname}'
                AND Col.`TABLE_NAME` = '{$tablename}'
                AND Col.`COLUMN_NAME` = '{$colname}'
            GROUP BY Col.`COLUMN_NAME`
            ORDER BY Col.`COLUMN_NAME`
        ";

        $result = $connection->query($query);

        if($connection->error) {
            throw new QueryErrorException($connection->error);
        }

        return $result->fetch_assoc();
    }

    /**
     * Get column relationships
     *
     * @return array of ReflectionColumn
     */
    protected function initializeColumnRelations()
    {
        $tablename = $this->table->getName();
        $connection = $this->getConnection();
        $colname = $this->getName();
        $dbname = $this->getDatabase()->getName();
        $relations = [];

        $queries = [
            // Query for get column references
            "
                SELECT `referenced_column_name` as colname, `referenced_table_name` as tablename
                FROM `information_schema`.`KEY_COLUMN_USAGE`
                WHERE `constraint_schema` = '{$dbname}'
                AND `table_name` = '{$tablename}' 
                AND `column_name` = '{$colname}'
                AND `referenced_column_name` IS NOT NULL 
            ",
            // Query for get columns who reference this column
            "
                SELECT `column_name` as colname, `table_name` as tablename
                FROM `information_schema`.`KEY_COLUMN_USAGE`
                WHERE `constraint_schema` = '{$dbname}'
                AND `referenced_table_name` = '{$tablename}' 
                AND `referenced_column_name` = '{$colname}'
                AND `column_name` IS NOT NULL 
            "
        ];

        foreach($queries as $query) {
            $result = $connection->query($query);
            if($connection->error) {
                throw new QueryErrorException($connection->error);
            }

            while($relation = $result->fetch_assoc()) {
                $tablename = $relation['tablename'];
                $colname = $relation['colname'];
                $relations[$tablename.'.'.$colname] = $relation;    
            }
        }

        $this->relations = $relations;
        $this->has_initialize_relations = true;
    }

}