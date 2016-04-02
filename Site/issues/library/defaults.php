<?php

/**
 * Default Config Settings
 * Below are the default config settings.
 * The config.php and defaults.php options are merged at run-time,
 * Copy any neccesary options from below into your config.php and make any changes you need there.
 */
return array(
    'app_name' => 'Bugify',
    'debug'    => false,
    'session'  => array(
        'name'           => 'bugify',
        'timeout'        => 1800,
        'path'           => '/tmp',
        'cookie_secure'  => false,
        'gc_divisor'     => 100,
        'gc_probability' => 1, //1% chance of garbage collection on request
    ),
    'upgrades' => array(
        'url'           => 'https://api.bugify.com/json/upgrades',
        'channel'       => 'stable',
        'show_reminder' => true,
    ),
    'install' => array(
        'remove_reminder' => true,
    ),
    'licence'     => '',
    'locale'      => 'en_NZ.utf8',
    'hosted'      => false,   //True if running as hosted version
    'limitations' => array(   //Hosted version only
        'max_projects' => '', //Leave blank for unlimited
        'max_users'    => '', //Leave blank for unlimited
        'max_size'     => '', //Leave blank for unlimited (this is total attachments size) (todo - implement this)
    ),
    'mail' => array(
        'port'     => 25,
        'ssl'      => '', //Either "tls" or "ssl"
        'auth'     => '', //Either "plain", "login" or "crammd5"
        'username' => '',
        'password' => '',
        'smtp'     => 'localhost',
    ),
    'cache'    => array(
        'lifetime'  => '3600',
        'cache_dir' => '/application/cache',
        'enabled'   => true,
    ),
    'storage' => array(
        'attachments' => '/application/storage/attachments',
    ),
    'layout' => array(
        'layout'     => 'main',
        'layoutPath' => '../application/views/layouts'
    ),
    'databases' => array(
        'type'   => 'sqlite', //Either "sqlite" or "mysql"
        'folder' => '/application/database', //SQLite only
        'files'  => array(
            'main' => 'bugify.db', //SQLite only
        ),
        'params' => array(
            'sqlite' => array(
                'charset'        => 'utf8',
                'profiler'       => false,
                'driver_options' => array(
                    'PDO_ATTR_TIMEOUT' => 30, //SQLite only
                ),
            ),
            'mysql' => array(
                'username' => '',
                'password' => '',
                'host'     => '',
                'dbname'   => '',
                'port'     => 3306,
                'charset'  => 'utf8',
                'profiler' => false,
            ),
        ),
    ),
    'web_user' => '',
    'logs'     => array(
       'path'     => '/application/logs',
       'filename' => 'bugify.log',
    ),
    'lucene'         => array(
       'index_path'  => '/application/lucene',
    ),
    'errors' => array(
       'verbose' => false,
    ),
    'demo' => array(
        'enabled'      => false,
        'username'     => 'demo',
        'password'     => 'demo',
        'reset_period' => 30, //Minutes
    ),
    'projects' => array(
       'default_categories' => array(
          'Bugs',
          'Features',
          'Ideas',
          'To Do',
       ),
    ),
    'css' => '', //Extra css for customisation
);
