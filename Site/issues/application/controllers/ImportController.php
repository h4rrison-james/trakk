<?php

class ImportController extends Ui_Controller_Action
{
    public function init()
    {}
    
    public function indexAction()
    {
        throw new Bugify_Exception('Import feature is not available.', 404);
        try
        {
            /*
            $i = new Importers_Redmine();
            
            $i->setUrl('http://www.redmine.org');
            
            $i->import();
            */
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
    }
}
