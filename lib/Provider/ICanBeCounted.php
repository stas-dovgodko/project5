<?php
namespace project5\Provider;

interface ICanBeCounted
{
    /**
     * @return int
     */
    public function getCount();

    /**
     * @return bool
     */
    public function canBeCounted();
}