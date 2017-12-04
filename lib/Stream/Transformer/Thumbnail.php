<?php
namespace project5\Stream\Transformer;

use Imagine\Gd\Imagine;
use project5\Stream\File;
use project5\Stream\IStreamable;
use project5\Stream\ITransformer;
use project5\Stream\String;

class Thumbnail implements ITransformer
{
    protected $width, $height;

    public function __construct($width, $height, $crop = false)
    {
        $this->width = $width;
        $this->height = $height;
        $this->crop = $crop;
    }

    /**
     * @param IStreamable $source
     * @return IStreamable
     */
    public function transform(IStreamable $source)
    {
        // make thumbnail
        $imagine = new Imagine();

        $image = $imagine->load($source->getContents());
        $size = $image->getSize();

        $width_k = $size->getWidth() / $this->width;
        $height_k = $size->getHeight() / $this->height;

        $k = max($width_k, $height_k, 1);

        return new String($image->resize(new \Imagine\Image\Box($size->getWidth() / $k, $size->getHeight() / $k))
            ->get('jpg'));
    }
}