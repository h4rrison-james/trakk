<?php

class Zend_View_Helper_ListRelatedIssues extends Zend_View_Helper_Abstract {
    
    public function ListRelatedIssues($originalIssue=array(), $relatedIssues=array()) {
        $html = '';
        
        if (is_array($relatedIssues) && count($relatedIssues) > 0) {
            $html .= '<table class="related-issues">';
            
            foreach ($relatedIssues as $key => $val) {
                $html .= sprintf('<tr class="related-issue-row-%s">', $val['id']);
                $html .= sprintf('<td class="column-issue-id"><div class="issue-id"><span>#</span>%s</div></td>', $val['id']);
                $html .= sprintf('<td><a href="/issues/%s">%s</a></td>', $val['id'], $val['subject']);
                $html .= '<td class="column-remove-relationship">';
                $html .= sprintf('<a href="#remove" onclick="removeRelationship(\'%s\', \'%s\'); return false;" title="Remove relationship">X</a>', $originalIssue['id'], $val['id']);
                $html .= '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        return $html;
    }
}
