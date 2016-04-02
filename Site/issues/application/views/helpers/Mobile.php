<?php

class Zend_View_Helper_Mobile extends Zend_View_Helper_Abstract
{
    /**
     * Find out if the user is browsing from a mobile device.
     */
    public function Mobile()
    {
        $is_mobile = $this->view->Os(true);
        
        return $is_mobile;
    }
}
