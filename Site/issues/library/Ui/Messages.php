<?php

class Ui_Messages
{
    private static $_validTypes = array(
       'ok',
       'info',
       'warning',
       'error',
       'upgrade',
    );
    
    public static function Add($type, $message)
    {
        if (in_array($type, self::$_validTypes))
        {
            //Save the message in the session
            $messages = new Zend_Session_Namespace('Messages');
            
            //Check the $message as it must be either array or string
            if (!is_array($message) && !is_string($message))
            {
                throw new Exception('The message must be either an array or a string.  The message was not saved.');
            }
            
            //Dont add duplicate messages
            $exists = false;
            
            if (isset($messages->list) && is_array($messages->list) && count($messages->list) > 0)
            {
                foreach ($messages->list as $key => $val)
                {
                    if ($val['type'] == $type && $val['message'] == $message)
                    {
                        $exists = true;
                        break;
                    }
                }
            }
            
            if ($exists === false)
            {
                $messages->list[] = array(
                    'type'    => (string) $type,
                    'message' => $message
                );
            }
        }
        else
        {
            throw new Exception('The specified message type is invalid.');
        }
    }
    
    public static function GetAll()
    {
        //Return all the messages in the session
        if (Zend_Session::namespaceIsset('Messages'))
        {
            $messages = new Zend_Session_Namespace('Messages');
            $list = (array) $messages->list;
        
            Zend_Session::namespaceUnset('Messages');
            
            if (is_array($list) && count($list) > 0)
            {
                return $list;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

}
