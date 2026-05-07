<?php
/**
 * @name SmartLogger.php
 * @desc Routes high-volume trace logs to a separate file while preserving operational logs.
 * @date: 07-05-2026
 * @path: ./util/Tally/v5/SmartLogger.php
 */

class SmartLogger {
    protected $operationalLogger;
    protected $traceLogger;

    function __construct($operationalFile, $traceFile) {
        $this->operationalLogger = new Log($operationalFile);
        $this->traceLogger = new Log($traceFile);
    }

    function write($text, $format = 'r') {
        $message = (string)$text;

        if ($this->isTraceMessage($message)) {
            $this->traceLogger->write($message, $format);
            return;
        }

        $this->operationalLogger->write($message, $format);
    }

    protected function isTraceMessage($message) {
        $normalized = strtolower(trim((string)$message));

        $traceSignals = array(
            'the sql is',
            'request is',
            'response is',
            'raw body content is',
            ' checking permissions',
            'the previous url is',
            'the current url is',
            'the apikey is:',
            'the new apikey is:',
            'pageno =',
            'pagecount =',
            ' returned data is',
            ' the userid is:'
        );

        foreach ($traceSignals as $signal) {
            if (strpos($normalized, $signal) !== false) {
                return true;
            }
        }

        $operationalSignals = array(
            'failed',
            'not successful',
            'internal server error',
            '[500]',
            'exception',
            'warning',
            'method not allowed',
            'not found',
            'no parameters were sent'
        );

        foreach ($operationalSignals as $signal) {
            if (strpos($normalized, $signal) !== false) {
                return false;
            }
        }

        if (preg_match('/\b(select|insert|update|delete)\b.*\bfrom\b/i', $message)) {
            return true;
        }

        return false;
    }
}
?>
