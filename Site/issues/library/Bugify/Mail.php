<?php
/**
 * Note: supported authentication types are (case-sensitive): plain, login, crammd5.
 *       supported SSL types are (case-sensitive): ssl, tls.
 */
class Bugify_Mail {
    private $_validAuthTypes = array(
        'plain',
        'login',
        'crammd5',
    );
    
    private $_validSslTypes = array(
        'ssl',
        'tls',
    );
    
    public function __construct() {}
    
    public function getMailer($setFrom=true) {
        $config = Zend_Registry::get('config');
        
        //Prepare the config settings for SMTP
        $smtpConfig = array(
            'port' => $config->mail->port,
        );
        
        if (strlen($config->mail->ssl) > 0) {
            if (in_array($config->mail->ssl, $this->_validSslTypes)) {
                $smtpConfig['ssl'] = $config->mail->ssl;
            }
        }
        
        if (strlen($config->mail->auth) > 0) {
            if (in_array($config->mail->auth, $this->_validAuthTypes)) {
                $smtpConfig['auth'] = $config->mail->auth;
            }
        }
        
        if (strlen($config->mail->username) > 0) {
            $smtpConfig['username'] = $config->mail->username;
        }
        
        if (strlen($config->mail->password) > 0) {
            $smtpConfig['password'] = $config->mail->password;
        }
        
        //Set the smtp transport
        $tr = new Zend_Mail_Transport_Smtp($config->mail->smtp, $smtpConfig);
        Zend_Mail::setDefaultTransport($tr);
        
        //Build the email
        $mail = new Zend_Mail('utf-8');
        $mail->addHeader('X-Generated-By', 'Bugify');
        
        if ($setFrom === true) {
            //Work out the from address
            $fromAddress = sprintf('noreply@%s', Bugify_Host::getHostname());
            
            $mail->setFrom($fromAddress, $config->app_name);
        }
        
        return $mail;
    }
}
