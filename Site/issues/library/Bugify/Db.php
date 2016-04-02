<?php

class Bugify_Db {
    const TYPE_SQLITE = 'sqlite';
    const TYPE_MYSQL  = 'mysql';
    
    public static function get() {
        //Generate the registry key
        $registry_key = 'db';
        
        //Check if we already have a connection
        if (Zend_Registry::isRegistered($registry_key)) {
            $db = Zend_Registry::get($registry_key);
        } else {
            //Load the database connection
            $config   = Zend_Registry::get('config');
            $settings = $config->databases->toArray();
            $params   = $settings['params'];
            
            //SQLite or MySQL?
            if ($settings['type'] == self::TYPE_SQLITE) {
                //SQLite database
                $filename = self::getSqliteFilename();
                
                //Append the db filename to the path
                $params['sqlite']['dbname'] = $config->base_path.$settings['folder'].'/'.$filename;
                
                //Check if the db file exists
                $upgradeSchema = (file_exists($params['sqlite']['dbname'])) ? false : true;
                
                //Load the databases
                $db = Zend_Db::factory('Pdo_Sqlite', $params['sqlite']);
                
                if ($upgradeSchema === true) {
                    /**
                     * Create the db structure.  We do this for SQLite databases because
                     * we have to check if the file exists anyway, so it is a cheap system
                     * check.  We don't do it for MySQL databases though because it requires
                     * a database query to list the tables.
                     */
                    $u = new Bugify_Db_Upgrade();
                    $u->upgradeDbSchema($db);
                }
            } elseif ($settings['type'] == self::TYPE_MYSQL) {
                //MySQL database
                $db = Zend_Db::factory('Pdo_Mysql', $params['mysql']);
            }
            
            //Store the connections
            Zend_Registry::set($registry_key, $db);
        }
        
        return $db;
    }
    
    public static function getDbType() {
        $config = Zend_Registry::get('config');
        $type   = (string)$config->databases->type;
        
        return $type;
    }
    
    public static function getSqliteFilename() {
        $filename = '';
        $config   = Zend_Registry::get('config');
        $settings = $config->databases->toArray();
        
        if (isset($settings['files']['main'])) {
            $filename = $settings['files']['main'];
        } else {
            throw new Bugify_Exception('The specified db is not valid.');
        }
        
        return $filename;
    }
    
    public static function deleteDb() {
        $config   = Zend_Registry::get('config');
        $settings = $config->databases->toArray();
        
        if ($settings['type'] == self::TYPE_SQLITE) {
            //Work out the full path to the SQLite db file
            $filename  = self::getSqliteFilename();
            $full_path = $config->base_path.$settings['folder'].'/'.$filename;
            
            if (file_exists($full_path)) {
                if (is_writable($full_path)) {
                    if (unlink($full_path) === true) {
                        return true;
                    } else {
                        throw new Bugify_Exception(sprintf('Could not delete "%s"', $filename));
                    }
                } else {
                    throw new Bugify_Exception(sprintf('Could not delete "%s" because it is not writable.', $filename));
                }
            }
        } elseif ($settings['type'] == self::TYPE_MYSQL) {
            //Delete the MySQL database (just drop all the tables)
            $t = new Bugify_Db_Tables();
            $tables = $t->getAllTables();
            
            //Load the db
            $db = self::get();
            
            foreach ($tables as $table) {
                //Drop the table
                $db->query('DROP TABLE IF EXISTS '.$table);
            }
        }
        
        return false;
    }
}
