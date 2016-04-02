<?php
class Zend_View_Helper_DisplayMessage extends Zend_View_Helper_Abstract
{
    private $_validTypes = array(
       'ok',
       'info',
       'warning',
       'critical',
       'error',
       'upgrade',
    );
    
    public function DisplayMessage($type, $message, $escape=true, $id=1)
    {
        if (in_array($type, $this->_validTypes))
        {
            $html  = '<div class="message message-'.$type.'" id="message-'.$id.'" style="display: none;">';
            $html .= '<a href="#" class="close" onclick="closeMessage(\''.$id.'\'); return false;">x</a>';
            
            if (is_string($message))
            {
                $message = ($escape) ? $this->view->escape($message) : $message;
                
                $html .= $this->view->Markdown($this->view->Linkify($message));
            }
            elseif (is_object($message))
            {
                //Attempt to convert it to a string
                $message = ($escape) ? $this->view->escape((string) $message) : (string) $message;
                
                if ($message != '')
                {
                    $html .= $this->view->Markdown($this->view->Linkify($message));
                }
                else
                {
                    $html .= '<p>You must supply an array or a string to the DisplayMessage method.</p>';
                }
            }
            elseif (is_array($message))
            {
                $html .= '<ol>';
            
                foreach ($message as $key => $val)
                {
                    if (is_string($val))
                    {
                        $message = ($escape) ? $this->view->escape($val) : $val;
                        
                        $html .= '<li>'.$this->view->Markdown($this->view->Linkify($message)).'</li>';
                    }
                    elseif (is_array($val))
                    {
                        foreach ($val as $skey => $sval)
                        {
                            $message = ($escape) ? $this->view->escape($sval) : $sval;
                            
                            $html .= '<li>'.$this->view->Markdown($this->view->Linkify($message)).'</li>';
                        }
                    }
                }
                
                $html .= '</ol>';
            }        
            
            $html .= '</div>';
            
            return $html;
        }
        else
        {
            throw new Ui_Exception('The specified message type is invalid.');
        }
    }
}
