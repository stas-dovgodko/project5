<?php
namespace project5\Upload;

use project5\Stream\IStreamable;
use StasDovgodko\Uri\File;
use StasDovgodko\Uri;

interface IStorage
{
    const PROPERTY_SIZE = 'size';
    const PROPERTY_TYPE = 'type';

    /**
     * Check if the container is writable
     *
     * @return bool
     */
    public function isWritable();

    /**
     * This will check if a file is in the container
     *
     * @param Uri $destination
     * @return bool
     */
    public function has(Uri $destination);

    /**
     * Saves the $content string as a file
     *
     * @param Uri $destination
     * @param IStreamable $content
     */
    public function save(Uri $destination, IStreamable $content);

    /**
     * Read the $content string as a resource
     *
     * @param Uri $destination
     * @return IStreamable
     */
    public function load(Uri $destination);

    /**
     * Delete the file from the container
     *
     * @param Uri $destination
     * @return bool
     */
    public function delete(Uri $destination);

    /**
     * Moves a temporary uploaded file to a destination in the container
     *
     * @param File $localFile   local path
     * @param Uri $destination
     * @return bool
     */
    public function moveUploadedFile(File $localFile, Uri $destination);

    /**
     * Get absolute accessible file
     *
     * @param Uri $destination
     * @return Uri
     */
    public function uri(Uri $destination);

    /**
     * Get accessible resource by uri
     *
     * @param Uri $uri
     * @return Uri
     */
    public function resource(Uri $uri);

    /**
     * Check if property supports
     *
     * @param string $property_name
     * @return bool
     */
    public function isSupportedProperty($property_name);

    /**
     * Get uploaded resource property
     *
     * @param Uri $destination
     * @param $property_name
     * @return mixed
     */
    public function getProperty(Uri $destination, $property_name);


}