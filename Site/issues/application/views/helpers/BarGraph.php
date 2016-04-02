<?php

class Zend_View_Helper_BarGraph extends Zend_View_Helper_Abstract
{
    public function BarGraph($percent)
    {
        //Make sure the percentage is an integer
        $percent = (int)$percent;
        $percent = ($percent < 100) ? $percent : 100;
        
        //Prepare the bar graph
        if ($percent < 100) {
            $html = '<div class="bar-graph">';
        } else {
            $html = '<div class="bar-graph bar-graph-full">';
        }
        
        if ($percent > 0) {
            $html .= '<div class="bar-graph-value" style="width: '.$percent.'%">'.$percent.'%</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
