<?php
namespace project5\Provider;

interface ICanBePaged
{
    /**
     * @param string $offser
     * @return void
     */
    public function setPagerOffset($offser);

    /**
     * @param int $limit
     * @return void
     */
    public function setPagerLimit($limit);

    /**
     * @return int
     */
    public function getPagerLimit();

    /**
     * @return int
     */
    public function getPagerOffset();

    /**
     * @return bool|null
     */
    public function hasNextPage();

    /**
     * @return bool|null
     */
    public function hasPrevPage();

    /**
     * @return string|null
     */
    public function getNextPageOffset();

    /**
     * @return string|null
     */
    public function getPrevPageOffset();
}