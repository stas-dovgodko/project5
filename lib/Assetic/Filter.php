<?php
namespace project5\Assetic;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Filter\BaseCssFilter;

use Assetic\Asset\AssetInterface;

use Doctrine\Common\Cache\Cache;
use project5\File;
use project5\Uri;
use project5\Web\Url;

class Filter extends BaseCssFilter
{
    /**
     * @var Factory
     */
    protected $factory;
    
    

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function filterLoad(AssetInterface $asset)
    {
        static $Processed = null; if ($Processed === null) $Processed = new \SplObjectStorage();

        if ($Processed->contains($asset)) {
            return;
        }

        $Processed->attach($asset);

        $sourcePath = $asset->getSourcePath();
        $targetPath = $asset->getTargetPath();

        if (null === $sourcePath || null === $targetPath || $sourcePath == $targetPath) {
            return;
        }

        $path = '';
        $target_path = $targetPath;

        while($target_path) {
            $dn = dirname($target_path);
            if (!$dn || $dn == $target_path || $dn == '.') {
                break;
            } else {
                $target_path = $dn;
            }
            $path .= '../';
        }

        $key = 'filter_css_v2_'.$sourcePath.'_'.$targetPath.'_'.md5($asset->getContent());



        if (false && is_array($data = $this->factory->getCache()->fetch($key))) {
            foreach ($data['refs'] as $ref_asset) {
                echo '.';
                $this->factory->addResource($asset, clone $ref_asset);
            }

            $asset->setContent($data['content']);
        } else {


            $collection = [];
            $content = $this->filterReferences($asset->getContent(), function ($matches) use ($path, $asset, &$collection) {

                if (0 === strpos($matches['url'], 'data:')) {
                    // data uri
                    return $matches[0];
                } elseif (false !== strpos($matches['url'], '://') || 0 === strpos($matches['url'], '//')) {
                    // absolute or protocol-relative or data uri

                    $url = new Url($matches['url']);

                    $collection[] = $ref_asset = new HttpAsset($matches['url'], [], true, $asset->getVars());


                    if ($ext = pathinfo($url->getPath(), PATHINFO_EXTENSION)) {
                        $name = basename($url->getPath(), '.' . $ext) . '_' . $ref_asset->getLastModified() . '.' . $ext;
                    } else {
                        $name = basename($url->getPath()) . '_' . $ref_asset->getLastModified();
                    }

                    $name = 'ref/' . $name;

                    $this->factory->addResource($asset, $ref_asset)->setTargetPath($name);

                    return 'url(' . $path . $name . ')';
                } elseif (isset($matches['url'][0]) && '/' == $matches['url'][0]) {
                    // root relative

                    $ref_uri = new Uri($matches['url']);

                    return 'url(' . (string)$ref_uri->getRelated(new Uri($asset->getSourceRoot() . $asset->getSourcePath())) . ')';// str_replace($matches['url'], $host.$matches['url'], $matches[0]);
                } else {


                    $url = new File($asset->getSourceRoot() . '/' . $asset->getSourcePath());

                    $url = $url->resolve(new File($matches['url']));

                    $filename = $url->getPath();

                    if (is_file($filename)) {

                        $collection[] = $ref_asset = new FileAsset($filename, [], null, null, $asset->getVars());


                        if ($ext = pathinfo($filename, PATHINFO_EXTENSION)) {
                            $name = basename($filename, '.' . $ext) . '_' . md5_file($filename) . '.' . $ext;
                        } else {
                            $name = basename($filename) . '_' . md5_file($filename);
                        }



                        $name = 'ref/' . $name;

                        $this->factory->addResource($asset, clone $ref_asset)->setTargetPath($name);

                        return 'url(' . $path . $name . ')';
                    } else {
                        return $matches[0];
                    }
                }
            });

            $this->factory->getCache()->save($key, [
                'content' => $content,
                'refs' => $collection,
            ]);


            $asset->setContent($content);
        }
    }

    /**
     * Filters an asset just before it's dumped.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterDump(AssetInterface $asset) {

    }

}