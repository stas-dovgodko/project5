<?php
namespace project5\Upload\Storage;

use project5\LoggerAwareTrait;
use project5\Upload\IStorage;
use project5\Stream\IStreamable;
use project5\Stream\File as FileStream;
use project5\File;
use project5\Uri;
use Psr\Log\LoggerAwareInterface;

class Local implements IStorage, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var File
     */
    protected $baseDirectory;

    /**
     * @var Uri|null
     */
    protected $baseUri;

    protected $chmod;

    public function __construct($baseDirectory, $baseUri = '/', $chmod = null)
    {
        $this->baseDirectory = ($baseDirectory instanceof File) ? $baseDirectory : new File($baseDirectory);
        $this->baseUri = ($baseUri instanceof Uri) ? $baseUri : new Uri($baseUri);

        $this->chmod = $chmod;
        //$this->ensureDirectory($this->baseDirectory);
    }

    protected function ensureDirectory(File $directory)
    {
        $dirname = $directory->getName();
        if (!is_dir($dirname)) {

            if (!@mkdir($dirname, 0777, true)) {
                throw new \RuntimeException('Can\'t access/create "'.$dirname.'" for upload');
            }
        }

        $this->log(sprintf('Ensure that %s is writable(%s) dir(%s)', $dirname, @is_writable($dirname)?'Y':'N', @is_dir($dirname)?'Y':'N'));


        return is_dir($dirname) && is_writable($dirname);
    }

    /**
     * Check if the container is writable
     *
     * @return bool
     */
    public function isWritable()
    {
        return true;
    }

    /**
     * This will check if a file is in the container
     *
     * @param Uri $destination
     * @return bool
     */
    public function has(Uri $destination)
    {
        if ($destination->getPath()) {
            return file_exists($this->baseDirectory->resolve($destination)->getPath());
        }
    }

    /**
     * Saves the $content string as a file
     *
     * @param Uri $destination
     * @param IStreamable $content
     */
    public function save(Uri $destination, IStreamable $content)
    {

        $dest_file = new File((string)$this->baseDirectory->resolve($destination));

        /** @var $dest_file File */

        if ($this->ensureDirectory($dest_file->getDirectory())) {
            return (bool)file_put_contents($dest_file->getPath(), $content->getContents());
        }
        return false;
    }

    /**
     * Read the $content string as a resource
     *
     * @param Uri $destination
     * @return IStreamable
     */
    public function load(Uri $destination)
    {
        $dest_file = $this->baseDirectory->resolve($destination);
        /** @var $dest_file File */

        return new FileStream($dest_file);
    }

    /**
     * Delete the file from the container
     *
     * @param Uri $destination
     * @return bool
     */
    public function delete(Uri $destination)
    {
        $dest_file = $this->baseDirectory->resolve(new File((string)$destination));
        /** @var $dest_file File */

        if (file_exists($dest_file->getPath())) {
            return unlink($dest_file->getPath());
        }
        return true;
    }

    /**
     * Moves a temporary uploaded file to a destination in the container
     *
     * @param File $localFile local path
     * @param Uri $destination
     * @return bool
     */
    public function moveUploadedFile(File $localFile, Uri $destination)
    {
        $dest_file = $this->baseDirectory->resolve(new File((string)$destination));
        /** @var $dest_file File */

        if ($this->ensureDirectory( $dest_file->getDirectory()) && file_exists($tmp_filename = $localFile->getPath())) {

            $this->log(sprintf('Tries to rename %s[%d size] file to %s', $tmp_filename, filesize($tmp_filename), $dest_file->getPath()));

            //if (is_uploaded_file($localFile->getPath())) {
                if (rename($tmp_filename, $filename = $dest_file->getPath())) {

                    if ($this->chmod) {

                        if (chmod($filename, $this->chmod) && $this->logger) {
                            $this->logger->debug(sprintf('Chmod\'ed %s to %d', $filename, $this->chmod));
                        }
                    }

                    return true;
                } else {
                    throw new \RuntimeException('Unexpected upload error - can\'t move file');
                }
            /*} else {
                throw new \RuntimeException('Unexpected upload error to '.$dest_file->getPath());
            }*/
        } else {
            throw new \RuntimeException('Can\'t access/create "'.$dest_file->getDirectory().'" for upload or "'.$localFile->getPath().'" file missed');
        }
        return false;
    }

    /**
     * Get absolute accessible file
     *
     * @param Uri $destination
     * @return Uri
     */
    public function uri(Uri $destination)
    {
        $root = new Uri('/');
        if ($this->baseUri) {
            return $root->resolve($this->baseUri)->resolve($destination);
        } else {
            return $root->resolve($destination);
        }

    }

    /**
     * Get accessible resource by uri
     *
     * @param Uri $uri
     * @return Uri
     */
    public function resource(Uri $uri)
    {
        $root = new Uri('/');
        $root = $root->resolve($this->baseUri);

        return $uri->getRelated($root);
    }

    /**
     * Check if property supports
     *
     * @param string $property_name
     * @return bool
     */
    public function isSupportedProperty($property_name)
    {
        return in_array($property_name, [
            self::PROPERTY_SIZE,
            self::PROPERTY_TYPE,
        ]);
    }

    /**
     * Get uploaded resource property
     *
     * @param Uri $destination
     * @param $property_name
     * @return mixed
     */
    public function getProperty(Uri $destination, $property_name)
    {
        $root = new Uri('/');
        $root = $root->resolve($this->baseUri);

        $destination = $destination->getRelated($root);
        $dest_file = $this->baseDirectory->resolve(new File((string)$destination))->getName();

        if (is_file($dest_file)) {
            switch ($property_name) {
                case self::PROPERTY_SIZE:
                    return filesize($dest_file);
                case self::PROPERTY_TYPE:
                    return mime_content_type($dest_file);
                default:
                    throw new \InvalidArgumentException('Wrong property name');

            }
        } else {
            return null;
        }
    }
}