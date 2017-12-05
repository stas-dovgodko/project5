<?php
/**
 * Created by PhpStorm.
 * User: Stas
 * Date: 1/14/2016
 * Time: 9:14 PM
 */
namespace project5\Assetic;

use Assetic\Asset\AssetCollection;
use Assetic\Factory\Worker\WorkerInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\AssetWriter;
use Assetic\Util\VarUtils;
use Doctrine\Common\Cache\Cache;

class Worker implements WorkerInterface
{
    protected $output;
    protected $base;

    protected $chmod;
    protected $ignoreFile = false;

    /**
     * @var Cache
     */
    protected $cache;

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function setChmod($mode)
    {
        $this->chmod = $mode;
    }

    public function setIgnoreFile($flag = true)
    {
        $this->ignoreFile = $flag;
    }

    /**
     * Processes an asset.
     *
     * @param AssetInterface $asset   An asset
     * @param AssetFactory   $factory The factory
     *
     * @return AssetInterface|null May optionally return a replacement asset
     */
    public function process(AssetInterface $asset, AssetFactory $factory)
    {
        if (!$path = $asset->getTargetPath()) {
            // no path to work with
            return;
        }

        $get_filename = function(AssetInterface $asset) {

            $filename = rtrim($this->output, '/') . '/' . VarUtils::resolve(
                $asset->getTargetPath(),
                $asset->getVars(),
                $asset->getValues()
            );

            return str_replace('//', '/', $filename);
        };

        $files = [];
        if (!is_file($filename = $get_filename($asset)) || $this->ignoreFile) {

            if (!is_dir($dir = dirname($filename))) {
                @mkdir($dir, $this->chmod ? $this->chmod : 0777, true);
            }
            if (@file_put_contents($filename, $asset->dump(), LOCK_EX) !== false && $this->chmod) {
                $files[] = $filename;
            }
        }

        if ($factory instanceof Factory) {
            //$refs = $factory->getRefs();
            foreach ($factory->getResources($asset) as $res_asset) {

                if (!is_file($filename = $get_filename($res_asset)) || $this->ignoreFile) {
                    if (!is_dir($dir = dirname($filename))) {
                        @mkdir($dir, $this->chmod ? $this->chmod : 0777, true);
                    }

                    if (@file_put_contents($filename, $res_asset->dump(), LOCK_EX) !== false && $this->chmod) {
                        $files[] = $filename;
                    }
                }
            }
        }


        if ($this->chmod && !empty($files)) {

            $chmod_dirs = [];

            chmod($this->output, $this->chmod);

            foreach (array_unique($files) as $file) {


                $dirname = dirname($file);
                if (!array_key_exists($dirname, $chmod_dirs)) {
                    $chmod_dirs[] = $dirname;
                    @chmod($dirname, $this->chmod);
                }

                @chmod($file, $this->chmod);
            }
        }
    }
}