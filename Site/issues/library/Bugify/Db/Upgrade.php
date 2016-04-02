<?php

class Bugify_Db_Upgrade {
    public function __construct() {}
    
    /**
     * Compare the current db schema with the expected
     * schema, and make changes where necessary.
     */
    public function upgradeDbSchema($db=null) {
        if ($db == null) {
            //Connect to the db
            $db = Bugify_Db::get();
        }
        
        //Compare required tables against existing tables
        $t = new Bugify_Db_Tables();
        
        $required_tables = $t->getAllTables();
        $existing_tables = $db->listTables();
        
        if (is_array($required_tables) && count($required_tables) > 0) {
            foreach ($required_tables as $key => $val) {
                if (!in_array($val, $existing_tables)) {
                    //Create this table
                    $schema = $t->getTableDefinition($val);
                    $query  = $this->generateCreateTableQuery($val, $schema);
                    
                    try {
                        $db->beginTransaction();
                        $db->query($query);
                        $db->commit();
                    } catch (Exception $e) {
                        $db->rollback();
                        
                        throw $e;
                    }
                } else {
                    //Check the columns in this table
                    $columns = $db->describeTable($val);
                    $schema  = $t->getTableDefinition($val);
                    
                    foreach ($schema['columns'] as $k => $v) {
                        if (!array_key_exists($k, $columns)) {
                            //This column doesnt exist, add it
                            $query = $this->generateAlterTableQuery($val, $k, $v);
                            
                            try {
                                $db->beginTransaction();
                                $db->query($query);
                                $db->commit();
                            } catch (Exception $e) {
                                $db->rollback();
                                
                                throw $e;
                            }
                        }
                    }
                }
            }
        }
    }
    
    public function generateCreateTableQuery($table_name, $schema) {
        $dbType = Bugify_Db::getDbType();
        $sql    = '';
        
        if ($dbType == Bugify_Db::TYPE_SQLITE) {
            $sql = $this->_generateSqliteCreateTableQuery($table_name, $schema);
        } elseif ($dbType == Bugify_Db::TYPE_MYSQL) {
            $sql = $this->_generateMysqlCreateTableQuery($table_name, $schema);
        }
        
        return $sql;
    }
    
    private function _generateSqliteCreateTableQuery($table_name, $schema) {
        $columns = $schema['columns'];
        $indexes = (isset($schema['indexes'])) ? $schema['indexes'] : array();
        
        $sql = 'CREATE TABLE '.$table_name.' ('."\n";
        
        $i = 0;
        
        foreach ($columns as $column => $options) {
            if ($options['type'] == 'VARCHAR') {
                $options['type'] = 'TEXT';
            }
            
            $sql .= sprintf('%s %s', $column, $options['type']);
            
            if (isset($options['primary']) && $options['primary'] == true) {
                $sql .= ' PRIMARY KEY';
            }
            
            if (isset($options['auto_increment']) && $options['auto_increment'] == true) {
                $sql .= ' AUTOINCREMENT';
            }
            
            if (isset($options['default'])) {
                $sql .= ' DEFAULT '.$options['default'];
            }
            
            $i++;
            
            if ($i < count($columns)) {
                $sql .= ',';
            }
            
            $sql .= "\n";
        }
        
        $sql .= ');';
        
        //Create any indexes
        if (is_array($indexes) && count($indexes) > 0) {
            $sql .= "\n".$this->generateCreateIndexesQuery($table_name, $indexes);
        }
        
        return $sql;
    }
    
    private function _generateMysqlCreateTableQuery($table_name, $schema) {
        $columns = $schema['columns'];
        $indexes = (isset($schema['indexes'])) ? $schema['indexes'] : array();
        
        $sql = 'CREATE TABLE '.$table_name.' ('."\n";
        
        $i = 0;
        
        foreach ($columns as $column => $options) {
            if ($options['type'] == 'VARCHAR') {
                $options['type'] = 'VARCHAR(255)';
            }
            
            $sql .= sprintf('%s %s', $column, $options['type']);
            
            if (isset($options['auto_increment']) && $options['auto_increment'] == true) {
                $sql .= ' AUTO_INCREMENT';
            }
            
            if (isset($options['primary']) && $options['primary'] == true) {
                $sql .= ' PRIMARY KEY';
            }
            
            if (isset($options['default'])) {
                $sql .= ' DEFAULT \''.$options['default'].'\'';
            }
            
            $i++;
            
            if ($i < count($columns)) {
                $sql .= ',';
            }
            
            $sql .= "\n";
        }
        
        $sql .= ') ENGINE = INNODB;';
        
        //Create any indexes
        if (is_array($indexes) && count($indexes) > 0) {
            $sql .= "\n".$this->generateCreateIndexesQuery($table_name, $indexes);
        }
        
        return $sql;
    }
    
    public function generateCreateIndexesQuery($table_name, $indexes) {
        $dbType = Bugify_Db::getDbType();
        $sql    = '';
        
        if ($dbType == Bugify_Db::TYPE_SQLITE) {
            $sql = $this->_generateSqliteCreateIndexesQuery($table_name, $indexes);
        } elseif ($dbType == Bugify_Db::TYPE_MYSQL) {
            $sql = $this->_generateMysqlCreateIndexesQuery($table_name, $indexes);
        }
        
        return $sql;
    }
    
    private function _generateSqliteCreateIndexesQuery($table_name, $indexes) {
        $sql = '';
        
        foreach ($indexes as $index_name => $options) {
            if (isset($options['unique']) && $options['unique'] == true) {
                $sql .= 'CREATE UNIQUE INDEX ';
            } else {
                $sql .= 'CREATE INDEX ';
            }
            
            $sql .= $index_name.' ON (';
            
            $i = 0;
            
            foreach ($options['fields'] as $field) {
                $sql .= $field;
                
                $i++;
                
                if ($i < count($options['fields'])) {
                    $sql .= ', ';
                }
            }
            
            $sql .= ');';
        }
        
        return $sql;
    }
    
    private function _generateMysqlCreateIndexesQuery($table_name, $indexes) {
        $sql = '';
        
        foreach ($indexes as $index_name => $options) {
            $sql .= 'ALTER TABLE '.$table_name;
            
            if (isset($options['unique']) && $options['unique'] == true) {
                $sql .= ' ADD UNIQUE ';
            } else {
                $sql .= ' ADD INDEX ';
            }
            
            $sql .= $index_name.' (';
            
            $i = 0;
            
            foreach ($options['fields'] as $field) {
                $sql .= $field;
                
                $i++;
                
                if ($i < count($options['fields'])) {
                    $sql .= ', ';
                }
            }
            
            $sql .= ');';
        }
        
        return $sql;
    }
    
    public function generateAlterTableQuery($table_name, $column, $options) {
        $dbType = Bugify_Db::getDbType();
        $sql    = '';
        
        if ($dbType == Bugify_Db::TYPE_SQLITE) {
            $sql = $this->_generateSqliteAlterTableQuery($table_name, $column, $options);
        } elseif ($dbType == Bugify_Db::TYPE_MYSQL) {
            $sql = $this->_generateMysqlAlterTableQuery($table_name, $column, $options);
        }
        
        return $sql;
    }
    
    private function _generateSqliteAlterTableQuery($table_name, $column, $options) {
        if ($options['type'] == 'VARCHAR') {
            $options['type'] = 'TEXT';
        }
        
        $sql  = 'ALTER TABLE '.$table_name.' '."\n";
        $sql .= 'ADD COLUMN '.$column.' '.$options['type'];
        
        if (isset($options['default'])) {
            $sql .= ' DEFAULT '.$options['default'];
        }
        
        $sql .= ';';
        
        return $sql;
    }
    
    private function _generateMysqlAlterTableQuery($table_name, $column, $options) {
        if ($options['type'] == 'VARCHAR') {
            $options['type'] = 'VARCHAR(255)';
        }
        
        $sql  = 'ALTER TABLE '.$table_name.' '."\n";
        $sql .= 'ADD '.$column.' '.$options['type'];
        
        if (isset($options['default'])) {
            $sql .= ' DEFAULT \''.$options['default'].'\'';
        }
        
        $sql .= ';';
        
        return $sql;
    }
}
