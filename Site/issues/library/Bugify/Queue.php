<?php

class Bugify_Queue {
    public function __construct() {}
    
    public function fetchPending($limit=10) {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('queue')
          ->where('state = ?', Bugify_Queue_Job::STATE_PENDING)
          ->limit($limit);
        
        $result = $db->fetchAll($s);
        $jobs   = array();
        
        if (is_array($result) && count($result) > 0) {
            foreach ($result as $key => $val) {
                //Load into object
                $j = new Bugify_Queue_Job();
                $j->setJobId($val['id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setStarted($val['started'])
                  ->setFinished($val['finished'])
                  ->setMethod($val['method'])
                  ->setRawParams($val['params'])
                  ->setMessage($val['message'])
                  ->setState($val['state']);
                
                $jobs[] = $j;
            }
        }
        
        return $jobs;
    }
    
    public function save(Bugify_Queue_Job $job) {
        if (!$job instanceof Bugify_Queue_Job) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Queue_Job.');
        }
        
        if ($job->getJobId() > 0) {
            //Update the database
            $data = array(
               'updated'  => time(),
               'started'  => Bugify_Date::getTimestamp($job->getStarted()),
               'finished' => Bugify_Date::getTimestamp($job->getFinished()),
               'method'   => $job->getMethod(),
               'params'   => $job->getParamsAsString(),
               'message'  => $job->getMessage(),
               'state'    => $job->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $job->getJobId());
            
            $db->update('queue', $data, $where);
        } else {
            //Insert as new job
            $data = array(
               'created'  => time(),
               'updated'  => time(),
               'started'  => Bugify_Date::getTimestamp($job->getStarted()),
               'finished' => Bugify_Date::getTimestamp($job->getFinished()),
               'method'   => $job->getMethod(),
               'params'   => $job->getParamsAsString(),
               'message'  => $job->getMessage(),
               'state'    => $job->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('queue', $data);
            
            $job_id = $db->lastInsertId();
            
            return $job_id;
        }
    }
    
    public function markJobAsRunning(Bugify_Queue_Job $job) {
        if (!$job instanceof Bugify_Queue_Job) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Queue_Job.');
        }
        
        $data = array(
           'updated' => time(),
           'started' => Bugify_Date::getTimestamp($job->getStarted()),
           'state'   => Bugify_Queue_Job::STATE_RUNNING,
        );
        
        $db = Bugify_Db::get();
        
        $where   = array();
        $where[] = $db->quoteInto('id = ?', $job->getJobId());
        $where[] = $db->quoteInto('state = ?', Bugify_Queue_Job::STATE_PENDING);
        
        $count = $db->update('queue', $data, $where);
        
        return ($count == 1);
    }
    
    public function start() {
        //Start the queue - work out the path to the cli tool
        $config = Zend_Registry::get('config');
        $path   = realpath(sprintf('%s/cli/tools/queue', $config->base_path));
        
        //Run the cli tool to start the queue
        exec(sprintf('php %s > /dev/null 2>&1 &', $path));
    }
}
