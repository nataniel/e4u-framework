<?php
namespace E4u\Tools\SQLLogger;

use Doctrine\DBAL\Logging\SQLLogger;
use E4u\Common\Time;

class Memory implements SQLLogger
{
    protected static $_logs = [];
    protected $time;

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        self::$_logs[] = [
            'sql' => $sql,
            'params' => $params,
            'types' => $types,
        ];
        
        $this->time = Time::getMicrotime();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        if ($this->time > 0) {
            $last = count(self::$_logs)-1;
            $time = Time::getMicrotime() - $this->time;
            self::$_logs[$last]['time'] = $time;
        }
        
        $this->time = null;
        return $this;
    }
    
    /**
     * @param  int $minTime
     * @return array
     */
    public static function getLog($minTime = 0)
    {
        if (0 === $minTime) {
            return self::$_logs;
        }

        $logs = [];
        foreach (self::$_logs as $log) {
            if ($log['time'] > $minTime) {
                $logs[] = $log;
            }
        }

        return $logs;
    }
}