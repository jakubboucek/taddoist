<?php
declare(strict_types=1);

namespace App\Model\Google;

use Tracy\ILogger;

class Logger implements ILogger
{
    /**
     * @var \Google\Cloud\Logging\Logger
     */
    private $logger;


    /**
     * @param \Google\Cloud\Logging\Logger $logger
     */
    public function __construct(\Google\Cloud\Logging\Logger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @param $value
     * @param string $priority
     */
    public function log($value, $priority = ILogger::INFO): void
    {
        $logData = $this->prepareLogData($value);

        $options = [
            'severity' => $this->translateSeverity($priority),
        ];

        $this->logger->write($logData, $options);
    }


    /**
     * @param $severity
     * @return int
     */
    private function translateSeverity($severity): int
    {
        switch ($severity) {
            case ILogger::DEBUG:
                return \Google\Cloud\Logging\Logger::DEBUG;
                break;
            case ILogger::INFO:
                return \Google\Cloud\Logging\Logger::INFO;
                break;
            case ILogger::WARNING:
                return \Google\Cloud\Logging\Logger::WARNING;
                break;
            case ILogger::ERROR:
            case ILogger::EXCEPTION:
                return \Google\Cloud\Logging\Logger::ERROR;
                break;
            case ILogger::CRITICAL:
                return \Google\Cloud\Logging\Logger::CRITICAL;
                break;
            default:
                return \Google\Cloud\Logging\Logger::DEFAULT_LEVEL;
        }
    }


    private function prepareLogData($data)
    {
        $type = \gettype($data);

        switch ($type) {
            case 'string':
                return $data;
                break;
            case 'NULL':
            case 'boolean':
            case 'integer':
            case 'double':
                return json_encode($data);
                break;
            case 'array':
                return array_map([$this, 'prepareLogData'], $data);
                break;
        }

        //Otherwise
        if($data instanceof \Throwable || ($type === 'object' && method_exists($data, '__toString'))) {
            return (string) $data;
        }

        return var_export($data, true);
    }
}
