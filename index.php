<?php
/**
 * @name index.php
 * @desc This file is part of the etaxware-api app. This is the entry point of the etaxware-api app
 * @date: 25-04-2026
 * @file: index.php
 * @path: ./index.php
 * @author: francis lubanga <francis.lubanga@gmail.com>
 * @copyright  (C) d'alytics - All Rights Reserved
 * @version    1.0.0
 */
// load f3
$f3 = require ("vendor/bcosca/fatfree-core/base.php");

// load configurations
$f3->config("config/config.ini");

// Resolve DB password securely with this precedence:
// 1) Environment variable (dbpwd_env)
// 2) Base64 value in config (dbpwd_b64)
// 3) Plain value in config (dbpwd, legacy fallback)
$dbPwdEnvKey = trim((string)$f3->get('dbpwd_env'));
$dbPwdFromEnv = '';

if ($dbPwdEnvKey !== '') {
    $dbPwdFromEnv = getenv($dbPwdEnvKey);
    $dbPwdFromEnv = ($dbPwdFromEnv === false) ? '' : trim((string)$dbPwdFromEnv);
}

$dbPwdB64 = trim((string)$f3->get('dbpwd_b64'));
$dbPwdFromB64 = '';

if ($dbPwdB64 !== '') {
    $decoded = base64_decode($dbPwdB64, true);
    if ($decoded === false) {
        throw new RuntimeException('Invalid base64 value for dbpwd_b64 in config/config.ini.');
    }
    $dbPwdFromB64 = trim((string)$decoded);
}

$dbPwdFromConfig = trim((string)$f3->get('dbpwd'));

$resolvedDbPwd = $dbPwdFromEnv;
if ($resolvedDbPwd === '') {
    $resolvedDbPwd = $dbPwdFromB64;
}
if ($resolvedDbPwd === '') {
    $resolvedDbPwd = $dbPwdFromConfig;
}

if ($resolvedDbPwd === '') {
    throw new RuntimeException('Database password is not configured. Set dbpwd_b64 in config/config.ini or set ' . $dbPwdEnvKey . ' in the environment.');
}

$f3->set('dbpwd', $resolvedDbPwd);

// load site routes
$f3->config("config/routes.ini");

// include some utilities
require 'vendor/autoload.php';

// start a new session as part of authentication
new Session();

// error logging
$f3->ONERROR = function($f3) {
    $f3->clear('CACHE'); //clear all CACHE contents
    
    $logger = new Log('error.log');
    $code = $f3->get('ERROR.code');
    $status = $f3->get('ERROR.status');
    $text = $f3->get('ERROR.text');
    $logger->write("[$code] - [$status] - [$text]");
    
    $f3->set('ERROR', $f3->get('ERROR'));
    
    //recursively clear existing output buffers
    while (ob_get_level())
        ob_end_clean();
    
    echo \Template::instance()->render('index.html');
    return FALSE;
};

// run f3
$f3->run();

?>