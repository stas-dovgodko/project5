<?php
namespace project5\Provider;

interface IHasShortView
{
    /**
     * @return bool
     */
    public function inShortState();

    /**
     * @param bool $state
     * @return void
     */
    public function setShortState($state = true);
}