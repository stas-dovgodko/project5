<?php
namespace project5\Template;

/**
 * Templating support
 *
 */
interface ITemplater
{
    /**
     * @param Render $render
     * @param string $content Template content
     * @param string $name Template name for debug or other reasons
     * @param array $arguments
     * @return mixed
     */
    public function render(Render $render, $content, $name, array $arguments = []);
}
