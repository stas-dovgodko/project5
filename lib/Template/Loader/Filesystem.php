<?php
namespace project5\Template\Loader;

use project5\Stream\File;
use project5\Stream\IStreamable;
use project5\Template\ILoader;
use Serializable;

class Filesystem implements ILoader, Serializable
{
    protected $prefix, $suffix;
    protected $paths = array();

    /**
     * Constructor.
     *
     * @param string|array $paths A path or an array of paths where to look for templates
     */
    public function __construct($paths = array(), $suffix = '', $prefix = '')
    {
        $this->prefix = $prefix;
        $this->suffix = $suffix;
        if ($paths) {
            $this->setPaths($paths);
        }
    }

    /**
     * Returns the paths to the templates.
     *
     * @return array The array of paths where to look for templates
     */
    public function getPaths()
    {
        return $this->path;
    }

    /**
     * Sets the paths where templates are stored.
     *
     * @param string|array $paths     A path or an array of paths where to look for templates
     */
    public function setPaths($paths)
    {
        if (!is_array($paths)) {
            $paths = array($paths);
        }

        $this->paths = $paths;
    }

    /**
     * Adds a path where templates are stored.
     *
     * @param string $path      A path where to look for templates
     *
     * @throws Exception\WrongPathException
     */
    public function addPath($path)
    {
        if (!is_dir($path)) {
            throw new Exception\WrongPathException(sprintf('The "%s" directory does not exist.', $path));
        }

        $this->paths[] = rtrim($path, '/\\');
    }

    /**
     * Prepends a path where templates are stored.
     *
     * @param string $path      A path where to look for templates
     *
     * @throws Exception\WrongPathException
     */
    public function prependPath($path)
    {
        if (!is_dir($path)) {
            throw new Exception\WrongPathException(sprintf('The "%s" directory does not exist.', $path));
        }

        $path = rtrim($path, '/\\');

        array_unshift($this->paths, $path);
    }

    protected function findTemplate($name)
    {
        if (false !== strpos($name, "\0")) {
            throw new Exception\WrongNameException('A template name cannot contain NUL bytes.');
        }

        $name = ltrim($name, '/');
        $parts = explode('/', $name);
        $level = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }

            if ($level < 0) {
                throw new Exception\WrongNameException(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
            }
        }

        foreach ($this->paths as $path) {

            $filename = $path.'/'.$this->prefix . $name . $this->suffix;

            if (is_file($filename)) {
                if (false !== $realpath = realpath($filename)) {
                    return $realpath;
                }

                return $filename;
            }
        }

        throw new Exception\NotFoundException(sprintf('Unable to find template "%s" (looked into: %s).', $this->prefix . $name . $this->suffix, implode(', ', $this->paths)));
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param string $name The name of the template to load
     *
     * @return IStreamable The template source code
     *
     * @throws Exception\NotFoundException When $name is not found
     */
    public function getSource($name)
    {
        return new File($this->findTemplate($name));
    }

    /**
     * Returns true if the template is was modified since time.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template should be reloaded if cached
     *
     * @throws Exception\NotFoundException When $name is not found
     */
    public function isModifiedSince($name, $time)
    {
        return filemtime($this->findTemplate($name)) > $time;
    }

    protected function globRecursive($path, $find, $base = '')
    {
        $dh = opendir($path);
        while (($file = readdir($dh)) !== false) {
            if (substr($file, 0, 1) == '.') continue;
            $rfile = "{$path}/{$file}";
            if (is_dir($rfile)) {
                foreach ($this->globRecursive($rfile, $find, $base . $file . DIRECTORY_SEPARATOR) as $ret) {
                    yield $ret;
                }
            } else {
                if (fnmatch($find, $file)) yield $base . basename(substr($rfile, strlen($this->prefix)), $this->suffix);
            }
        }
        closedir($dh);
    }

    /**
     * @return \Traversable
     */
    public function getNames()
    {
        foreach ($this->paths as $path) {

            foreach ($this->globRecursive($path, $this->prefix.'*'.$this->suffix) as $ret) {
                yield $ret;
            }
        }
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            $this->prefix, $this->suffix, $this->paths
        ]);
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list($this->prefix, $this->suffix, $this->paths) = unserialize($serialized);
    }
}