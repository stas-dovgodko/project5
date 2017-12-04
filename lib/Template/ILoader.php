<?php
namespace project5\Template;

use project5\Stream\IStreamable;

interface ILoader {

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return IStreamable The template source stream
     *
     * @throws Loader\Exception\NotFoundException When $name is not found
     */
    public function getSource($name);

    /**
     * Returns true if the template is was modified since time.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template should be reloaded if cached
     *
     * @throws Loader\Exception\NotFoundException When $name is not found
     */
    public function isModifiedSince($name, $time);

    /**
     * @return \Traversable
     */
    public function getNames();
}