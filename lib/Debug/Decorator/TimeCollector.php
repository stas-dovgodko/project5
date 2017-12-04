<?php
namespace project5\Debug\Decorator;

use DebugBar\DataCollector\TimeDataCollector;

trait TimeCollector {
    protected $timeDataCollector;

    public function setTimeDataCollector(TimeDataCollector $collector)
    {
        $this->timeDataCollector = $collector;
    }

    public function getTimeDataCollector()
    {
        return $this->timeDataCollector;
    }
}