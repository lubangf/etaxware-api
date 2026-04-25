<?php

function rotatelogifneeded($filePath, $maxBytes, $maxArchives) {
    $filePath = trim((string)$filePath);
    $maxBytes = (int)$maxBytes;
    $maxArchives = (int)$maxArchives;

    if ($filePath === '' || $maxBytes <= 0 || $maxArchives < 1) {
        return;
    }

    if (!file_exists($filePath)) {
        return;
    }

    clearstatcache(true, $filePath);
    $size = filesize($filePath);

    if ($size === false || $size < $maxBytes) {
        return;
    }

    $archive = $filePath . '.' . date('Ymd-His');
    if (file_exists($archive)) {
        $archive = $archive . '-' . uniqid();
    }

    if (!@rename($filePath, $archive)) {
        return;
    }

    @touch($filePath);

    $archives = glob($filePath . '.*');
    if (!is_array($archives)) {
        return;
    }

    usort($archives, function($a, $b) {
        $mtimeA = @filemtime($a);
        $mtimeB = @filemtime($b);
        if ($mtimeA === $mtimeB) {
            return 0;
        }
        return ($mtimeA < $mtimeB) ? 1 : -1;
    });

    $keep = 0;
    foreach ($archives as $archivedFile) {
        $keep = $keep + 1;
        if ($keep > $maxArchives) {
            @unlink($archivedFile);
        }
    }
}

function emitjsonerrorresponse($code, $message) {
    $httpCode = (int)$code;

    if ($httpCode < 100 || $httpCode > 599) {
        $httpCode = 500;
    }

    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : '';
    $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';

    while (ob_get_level()) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=UTF-8');
    }

    $response = array(
        'response' => array(
            'responseCode' => strval($httpCode),
            'responseMessage' => (string)$message
        ),
        'data' => array(
            'method' => $method,
            'path' => $uri
        )
    );

    echo json_encode($response);
}

try {
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

$logRotationEnabled = trim((string)$f3->get('log_rotation_enabled'));
$logRotationEnabled = ($logRotationEnabled === '') ? '1' : $logRotationEnabled;

$logRotateMaxMb = (int)$f3->get('log_rotate_max_mb');
if ($logRotateMaxMb <= 0) {
    $logRotateMaxMb = 20;
}

$logRotateMaxFiles = (int)$f3->get('log_rotate_max_files');
if ($logRotateMaxFiles <= 0) {
    $logRotateMaxFiles = 30;
}

if ($logRotationEnabled === '1') {
    $maxBytes = $logRotateMaxMb * 1024 * 1024;
    rotatelogifneeded(__DIR__ . DIRECTORY_SEPARATOR . 'error.log', $maxBytes, $logRotateMaxFiles);
    rotatelogifneeded(__DIR__ . DIRECTORY_SEPARATOR . 'api.log', $maxBytes, $logRotateMaxFiles);
    rotatelogifneeded(__DIR__ . DIRECTORY_SEPARATOR . 'api-trace.log', $maxBytes, $logRotateMaxFiles);
    rotatelogifneeded(__DIR__ . DIRECTORY_SEPARATOR . 'util.log', $maxBytes, $logRotateMaxFiles);
    rotatelogifneeded(__DIR__ . DIRECTORY_SEPARATOR . 'util-trace.log', $maxBytes, $logRotateMaxFiles);
}

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
    
    $httpCode = (int)$code;
    if ($httpCode < 100 || $httpCode > 599) {
        $httpCode = 500;
    }

    $friendlyMessage = $text;

    if ($httpCode === 405) {
        $friendlyMessage = 'Method Not Allowed. Use POST for API endpoints. For health checks, GET / is supported.';
    } elseif ($httpCode === 404) {
        $friendlyMessage = 'Endpoint not found. Verify the API URL and route.';
    } elseif ($httpCode >= 500) {
        $friendlyMessage = 'Internal server error. Contact your system administrator if the issue persists.';
    }

    emitjsonerrorresponse($httpCode, $friendlyMessage);
    return TRUE;
};

// run f3
$f3->run();

} catch (Throwable $e) {
    $logger = new Log('error.log');
    $logger->write('[500] - [Bootstrap Failure] - [' . $e->getMessage() . ']');
    emitjsonerrorresponse(500, 'Internal server error. Contact your system administrator if the issue persists.');
}

?>