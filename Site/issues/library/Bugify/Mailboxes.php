<?php

class Bugify_Mailboxes
{
    public function __construct()
    {}
    
    public function fetchAll()
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('mailboxes');
        
        $result    = $db->fetchAll($s);
        $mailboxes = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                //Load into object
                $m = new Bugify_Mailbox();
                $m->setMailboxId($val['id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setLastChecked($val['last_checked'])
                  ->setName($val['name'])
                  ->setHost($val['host'])
                  ->setPort($val['port'])
                  ->setUsername($val['username'])
                  ->setPassword($val['password'])
                  ->setProjectId($val['project_id'])
                  ->setCategoryId($val['category_id'])
                  ->setType($val['type'])
                  ->setEncryption($val['encryption'])
                  ->setState($val['state']);
                
                $mailboxes[] = $m;
            }
        }
        
        return $mailboxes;
    }
    
    /**
     * Fetch the specified mailbox from the database
     * 
     * @return Bugify_Mailbox
     */
    public function fetch($mailbox_id)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('mailboxes')
          ->where('id = ?', $mailbox_id)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Load into object
            $m = new Bugify_Mailbox();
            $m->setMailboxId($result['id'])
              ->setCreated($result['created'])
              ->setUpdated($result['updated'])
              ->setLastChecked($result['last_checked'])
              ->setName($result['name'])
              ->setHost($result['host'])
              ->setPort($result['port'])
              ->setUsername($result['username'])
              ->setPassword($result['password'])
              ->setProjectId($result['project_id'])
              ->setCategoryId($result['category_id'])
              ->setType($result['type'])
              ->setEncryption($result['encryption'])
              ->setState($result['state']);
            
            return $m;
        }
        else
        {
            throw new Bugify_Exception('The specified mailbox does not exist.', 404);
        }
    }
    
    public function save(Bugify_Mailbox $mailbox)
    {
        if (!$mailbox instanceof Bugify_Mailbox)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Mailbox.');
        }
        
        if ($mailbox->getMailboxId() > 0)
        {
            //Update the database
            $data = array(
               'updated'     => time(),
               'name'        => $mailbox->getName(),
               'host'        => $mailbox->getHost(),
               'port'        => $mailbox->getPort(),
               'username'    => $mailbox->getUsername(),
               'password'    => $mailbox->getPassword(),
               'project_id'  => $mailbox->getProjectId(),
               'category_id' => $mailbox->getCategoryId(),
               'type'        => $mailbox->getType(),
               'encryption'  => $mailbox->getEncryption(),
               'state'       => $mailbox->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $mailbox->getMailboxId());
            
            $db->update('mailboxes', $data, $where);
        }
        else
        {
            //Insert as new mailbox
            $data = array(
               'created'     => time(),
               'updated'     => time(),
               'name'        => $mailbox->getName(),
               'host'        => $mailbox->getHost(),
               'port'        => $mailbox->getPort(),
               'username'    => $mailbox->getUsername(),
               'password'    => $mailbox->getPassword(),
               'project_id'  => $mailbox->getProjectId(),
               'category_id' => $mailbox->getCategoryId(),
               'type'        => $mailbox->getType(),
               'encryption'  => $mailbox->getEncryption(),
               'state'       => $mailbox->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('mailboxes', $data);
            
            return $db->lastInsertId();
        }
    }
}
