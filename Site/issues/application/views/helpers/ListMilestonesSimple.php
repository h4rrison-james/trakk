<?php

class Zend_View_Helper_ListMilestonesSimple extends Zend_View_Helper_Abstract {
    
    public function ListMilestonesSimple($milestones=array()) {
        $html = '';
        
        if (is_array($milestones) && count($milestones) > 0) {
            $html .= '<table class="simple-milestones">';
            
            foreach ($milestones as $key => $val) {
                $html .= '<tr><td>';
                $html .= '<div class="simple-milestone-row">';
                $html .= sprintf('<a href="/milestones/%s">%s</a>', $val['id'], $val['name']);
                
                //Work out the percentage of closed issues
                $totalIssuesCount = ($val['closed_count']+$val['issue_count']);
                
                if ($totalIssuesCount > 0) {
                    $completePercentage = ($val['closed_count'] / $totalIssuesCount) * 100;
                } else {
                    $completePercentage = 0;
                }
                
                if (strlen($val['due'])) {
                    $html .= sprintf('<div class="meta">Due %s</div>', Bugify_Date::formatDate('EEE, d MMM yyyy', $val['due']));
                }
                
                $html .= '</div>';
                $html .= $this->view->BarGraph($completePercentage);
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        return $html;
    }
}
