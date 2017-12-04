<?php
namespace project5\Provider;

use project5\Provider\Compare\E\UnsupportedException;

interface ICanBeFiltered
{
    /**
     * @throws UnsupportedException
     * @param IField $field,
     * @param IComparer $comparer
     * @return ICanBeFiltered
     */
    public function filter(IField $field, IComparer $comparer);

    /**
     * Check if instance can be filtered
     *
     * @return ICanBeFiltered
     */
    public function resetFilter();

    /**
     * Get list of filterable fields
     *
     * @return IField[]
     */
    public function getFilterable();
}