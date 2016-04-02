<?php

class HistoryController extends Ui_Controller_Action {
    public function init()
    {}
    
    public function indexAction() {
        try {
            $history = array();
            $page    = 1;
            $limit   = 0;
            $total   = 0;
            
            //Prepare defaults
            $period  = '1week';
            $periods = array(
                '1week'  => array(
                    'time' => '-1 week',
                    'name' => 'Last Week',
                ),
                '2weeks' => array(
                    'time' => '-2 weeks',
                    'name' => 'Last Fortnight',
                ),
                '3weeks' => array(
                    'time' => '-3 weeks',
                    'name' => 'Last 3 Weeks',
                ),
                '1month' => array(
                    'time' => '-4 weeks',
                    'name' => 'Last Month',
                ),
            );
            
            $params = $this->_getAllParams();
            
            if (isset($params['period'])) {
                $period = $params['period'];
            }
            
            if (array_key_exists($period, $periods)) {
                $time = $periods[$period]['time'];
                
                //Load recent history
                $i = new Bugify_Issues();
                $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
                $result = $i->fetchHistory($time);
                
                //Process the history (ie, attach extra info)
                $h = new Bugify_Helpers_History();
                $history = $h->attachFullInfo($result, true);
                
                //Get the pagination info
                $page  = $i->getPaginationPage();
                $limit = $i->getPaginationLimit();
                $total = $i->getTotal();
            } else {
                throw new Bugify_Exception('The specified period is not valid.');
            }
        } catch (Exception $e) {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->history = $history;
        $this->view->page    = $page;
        $this->view->limit   = $limit;
        $this->view->total   = $total;
        $this->view->periods = $periods;
        $this->view->period  = $period;
    }
}
