<?php

class UpdateController extends Ui_Controller_Action {
    
    public function indexAction() {
        try {
            $upgrade        = array();
            $upgrade_exists = false;
            $last_checked   = '';
            $next_check     = '';
            
            $config = Zend_Registry::get('config');
            
            $u = new Bugify_Upgrades($config->upgrades->url, $config->upgrades->channel);
            $u->checkForUpgradeAsync();
            
            //Check if an upgrade exists
            $upgrade_exists = $u->upgradeExists();
            $last_checked   = $u->getLastChecked();
            $next_check     = $u->getNextCheck();
            
            if ($upgrade_exists) {
                $upgrade = $u->getUpgradeInfo();
            }
        } catch (Exception $e) {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->upgrade        = $upgrade;
        $this->view->upgrade_exists = $upgrade_exists;
        $this->view->last_checked   = $last_checked;
        $this->view->next_check     = $next_check;
    }
    
    public function checkAction()
    {
        try
        {
            //Force an upgrade check now
            $config = Zend_Registry::get('config');
            
            $u = new Bugify_Upgrades($config->upgrades->url, $config->upgrades->channel);
            $u->checkForUpgrade(true);
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->_redirect('/update');
    }
    
    public function nowAction()
    {
        try
        {
            //Install the latest version
            $config = Zend_Registry::get('config');
            
            $u = new Bugify_Upgrades($config->upgrades->url, $config->upgrades->channel);
            
            if ($u->upgradeExists())
            {
                $upgrade = $u->getUpgradeInfo();
                $u->doUpgrade();
                
                //Clear all caches
                $this->cache->removeAll();
                
                Ui_Messages::Add('ok', sprintf('Bugify has been updated to version %s', $upgrade['version']));
            }
            else
            {
                Ui_Messages::Add('warning', 'There no updates available at the moment.');
            }
            
            $this->_redirect('/');
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
            
            $this->_redirect('/update');
        }
    }
}
