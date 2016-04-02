<?php

class Bugify_Db_Tables {
    private $_tables = array(
       'projects' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'name' => array(
                'type' => 'VARCHAR',
             ),
             'slug' => array(
                'type' => 'VARCHAR',
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
          ),
          'indexes' => array(
             'slug' => array(
                'unique' => true,
                'fields' => array(
                   'slug',
                ),
             ),
          ),
       ),
       'issues' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'project_id' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'category_id' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'milestone_id' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'creator_id' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'assignee_id' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'resolved' => array(
                'type' => 'INTEGER',
             ),
             'subject' => array(
                'type' => 'VARCHAR',
             ),
             'description' => array(
                'type' => 'TEXT',
             ),
             'related_issues' => array(
                'type' => 'TEXT',
             ),
             'priority' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'percentage' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
          ),
          'indexes' => array(
             'project_id' => array(
                'unique' => false,
                'fields' => array(
                   'project_id',
                ),
             ),
             'category_id' => array(
                'unique' => false,
                'fields' => array(
                   'category_id',
                ),
             ),
          ),
       ),
       'categories' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'project_id' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'name' => array(
                'type' => 'VARCHAR',
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
          ),
          'indexes' => array(
             'project_id' => array(
                'unique' => false,
                'fields' => array(
                   'project_id',
                ),
             ),
          ),
       ),
       'milestones' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'due' => array(
                'type' => 'INTEGER',
             ),
             'name' => array(
                'type' => 'VARCHAR',
             ),
             'description' => array(
                'type' => 'TEXT',
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
          ),
       ),
       'followers' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'issue_id' => array(
                'type' => 'INTEGER',
             ),
             'user_id' => array(
                'type' => 'INTEGER',
             ),
          ),
       ),
       'comments' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'issue_id' => array(
                'type' => 'INTEGER',
             ),
             'user_id' => array(
                'type' => 'INTEGER',
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'comment' => array(
                'type' => 'TEXT',
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
          ),
       ),
       'attachments' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'issue_id' => array(
                'type' => 'INTEGER',
             ),
             'user_id' => array(
                'type' => 'INTEGER',
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'name' => array(
                'type' => 'VARCHAR',
             ),
             'filename' => array(
                'type' => 'VARCHAR',
             ),
             'filesize' => array(
                'type' => 'INTEGER',
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 0,
             ),
          ),
       ),
       'history' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'issue_id' => array(
                'type' => 'INTEGER',
             ),
             'user_id' => array(
                'type' => 'INTEGER',
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
          ),
       ),
       'history_changes' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'history_id' => array(
                'type' => 'INTEGER',
             ),
             'type' => array(
                'type' => 'INTEGER',
             ),
             'original' => array(
                'type' => 'TEXT',
             ),
             'new' => array(
                'type' => 'TEXT',
             ),
          ),
       ),
       'filters' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'user_id' => array(
                'type' => 'INTEGER',
             ),
             'name' => array(
                'type' => 'VARCHAR',
             ),
             'filter' => array(
                'type' => 'TEXT',
             ),
          ),
       ),
       'users' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'firstname' => array(
                'type' => 'VARCHAR',
             ),
             'lastname' => array(
                'type' => 'VARCHAR',
             ),
             'email' => array(
                'type' => 'VARCHAR',
             ),
             'username' => array(
                'type' => 'VARCHAR',
             ),
             'password' => array(
                'type' => 'VARCHAR',
             ),
             'api_key' => array(
                'type' => 'VARCHAR',
             ),
             'notifications' => array(
                'type' => 'TEXT',
             ),
             'timezone' => array(
                'type' => 'VARCHAR',
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 1,
             ),
          ),
          'indexes' => array(
             'username' => array(
                'unique' => true,
                'fields' => array(
                   'username',
                ),
             ),
          ),
       ),
       'mailboxes' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'last_checked' => array(
                'type' => 'INTEGER',
             ),
             'name' => array(
                'type' => 'VARCHAR',
             ),
             'host' => array(
                'type' => 'VARCHAR',
             ),
             'port' => array(
                'type' => 'INTEGER',
             ),
             'username' => array(
                'type' => 'VARCHAR',
             ),
             'password' => array(
                'type' => 'VARCHAR',
             ),
             'project_id' => array(
                'type' => 'INTEGER',
             ),
             'category_id' => array(
                'type' => 'INTEGER',
             ),
             'type' => array(
                'type' => 'INTEGER',
             ),
             'encryption' => array(
                'type' => 'INTEGER',
             ),
             'state' => array(
                'type'    => 'INTEGER',
                'default' => 1,
             ),
          ),
       ),
       'upgrades' => array(
          'columns' => array(
             'version' => array(
                'type' => 'VARCHAR',
             ),
             'released' => array(
                'type' => 'INTEGER',
             ),
             'link' => array(
                'type' => 'TEXT',
             ),
             'signature' => array(
                'type' => 'TEXT',
             ),
             'changelog' => array(
                'type' => 'TEXT',
             ),
             'upgrade_check_url' => array(
                'type' => 'TEXT',
             ),
             'last_checked' => array(
                'type' => 'INTEGER',
             ),
          ),
       ),
       'queue' => array(
          'columns' => array(
             'id' => array(
                'type'           => 'INTEGER',
                'primary'        => true,
                'auto_increment' => true,
             ),
             'created' => array(
                'type' => 'INTEGER',
             ),
             'updated' => array(
                'type' => 'INTEGER',
             ),
             'started' => array(
                'type' => 'INTEGER',
             ),
             'finished' => array(
                'type' => 'INTEGER',
             ),
             'method' => array(
                'type' => 'VARCHAR',
             ),
             'params' => array(
                'type' => 'TEXT',
             ),
             'message' => array(
                'type' => 'TEXT',
             ),
             'state' => array(
                'type' => 'INTEGER',
             ),
          ),
       ),
    );
    
    public function __construct() {}
    
    private function _getTables() {
        return $this->_tables;
    }
    
    public function getTableDefinition($table) {
        $tables = $this->_getTables();
        
        if (array_key_exists($table, $tables)) {
            return $tables[$table];
        } else {
            throw new Bugify_Exception('The specified table is not valid.');
        }
    }
    
    public function getAllTables() {
        $tables = $this->_getTables();
        
        return array_keys($tables);
    }
}
