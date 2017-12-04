<?php
namespace project5\Provider;

use project5\Provider\Sort\ISorter;
use project5\Provider\Sort\E\UnsupportedException;

interface ICanBeSorted
{
    /**
     * @return IField[]
     */
    public function getSortable();

    /**
     * @throws UnsupportedException
     * @param IField $field
     * @param ISorter $comparer
     * @return ICanBeSorted
     */
    public function sort(IField $field, ISorter $comparer);

    /**
     * @return ICanBeSorted
     */
    public function resetSorting();
}