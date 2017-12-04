<?php
namespace project5\Provider;

interface ICanBeIndexed
{
    /**
     * @param Index $index
     * @return ICanBeIndexed
     */
    public function setIndex(Index $index);

    /**
     * @return string
     */
    public function getIndexKey();
}