<?php
/**
 * Created by PhpStorm.
 * User: Stas
 * Date: 3/15/2016
 * Time: 1:18 PM
 */

namespace project5\Assetic;


use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\AssetManager;

class Reference extends AssetReference
{
    protected $protectedName;

    /**
     * @var AssetManager
     */
    protected $protectedAm;
    
    public function __construct(AssetManager $am, $name)
    {
        parent::__construct($am, $name);

        $this->protectedAm = $am;
        $this->protectedName = $name;
    }
    
    public function all($ext = null)
    {
        $asset = $this->protectedAm->get($this->protectedName);

        if ($asset instanceof  AssetCollection) {
            $list = $asset->all();
        } else {
            $list = [$asset];
        }

        $filtered = [];

        foreach($list as $asset) {
            if ($asset instanceof self) {

                foreach ($asset->all($ext) as $a) {
                    $source_path = $a->getSourcePath();
                    $filtered[realpath($a->getSourceRoot() .'/'.$source_path)] = $a;
                }

                //$filtered = array_merge($filtered, $asset->all($ext));
            } elseif ($asset instanceof AssetInterface) {
                $source_path = $asset->getSourcePath();
                if (fnmatch('*.'.$ext, basename($source_path))) {
                    $filtered[realpath($asset->getSourceRoot() .'/'.$source_path)] = $asset;
                }
            }

        }

        return $filtered;
    }
}