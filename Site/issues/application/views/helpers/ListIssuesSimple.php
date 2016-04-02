<?php

class Zend_View_Helper_ListIssuesSimple extends Zend_View_Helper_Abstract {
    
    public function ListIssuesSimple($issues=array()) {
        $html = '';
        
        if (is_array($issues) && count($issues) > 0) {
            $html .= '<table class="simple-issues issue-list">';
            
            foreach ($issues as $key => $val) {
                switch ($val['priority'])
                {
                    case Bugify_Issue::PRIORITY_LOW:
                        $priority_css = 'low';
                        break;
                    case Bugify_Issue::PRIORITY_NORMAL:
                        $priority_css = 'normal';
                        break;
                    case Bugify_Issue::PRIORITY_HIGH:
                        $priority_css = 'high';
                        break;
                    case Bugify_Issue::PRIORITY_CRITICAL:
                        $priority_css = 'critical';
                        break;
                }
                
                
                $html .= sprintf('<tr class="simple-issue-row-%s">', $val['id']);
                $html .= sprintf('<td class="column-issue-id"><div class="issue-id"><span>#</span>%s</div></td>', $val['id']);
                $html .= '<td>';
                $html .= sprintf('<a href="/issues/%s">%s</a>', $val['id'], $val['subject']);
                
                if ($val['percentage'] > 0) {
                    $html .= sprintf('<span class="percentage">%s%%</span>', $val['percentage']);
                }
                
                $html .= '</td>';
                
                //Category/state column
                $html .= '<td class="column-category">';
                $html .= '<div class="priority priority-'.$priority_css.'">'.$this->view->PriorityName($val['priority']).'</div>';
                $html .= '</td>';
                
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        return $html;
    }
}
