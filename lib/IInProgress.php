<?php
namespace project5;

/**
 *
 */
interface IInProgress
{
    /**
    * @return int
    */
    public function getProgress();

    /**
     * @return int|null
     */
    public function getProgressMax();

    /**
     * @return bool
     */
    public function progress();
}
