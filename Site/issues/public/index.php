<?php

/**
 * Just in case we are using a custom public folder (for example, with
 * cPanel, it's easier to just rename "public" to "public_html".
 */
define('PUBLIC_PATH', dirname(__FILE__));

require '../library/Bootstrap.php';
Bootstrap::start();
