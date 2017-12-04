<?php
namespace project5\Propel\Cli;

use project5\Propel\Exception\EntityNotFoundException;
use project5\Propel\Platform\MysqlPlatform;
use Propel\Generator\Config\GeneratorConfig;
use Symfony\Component\Console\Input\InputInterface;

trait Command
{
    protected $tmp;
    protected $connections;


    protected function getSchemas($directory, $recursive = false)
    {
        $finder = parent::getSchemas($directory, $recursive);

        $xincluded = [];
        foreach($finder as $filename => $fileInfo) { /** @var $fileInfo Symfony\Component\Finder\SplFileInfo */

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->load($filename);
            if ($this->tmp && $dom->xinclude()) {
                $tmp_file = tempnam($this->tmp, basename($filename));
                $dom->save($tmp_file);
                
                $xincluded[$tmp_file] = new \SplFileInfo($tmp_file);
            } else {
                $xincluded[$filename] = $fileInfo;
            }
        }
        
        return $xincluded;
    }

    protected function getGeneratorConfig(array $properties = null, InputInterface $input = null)
    {
        $adapter = null;
        foreach($this->connections as $name => $info) {
            $adapter = $info[0];
            $properties['propel']['database']['connections'][$name] = array_merge(
                ['adapter' => $adapter],
                $info[1]
            );
        }

        if (empty($properties['propel']['generator']['platformClass']) && strtolower($adapter) == 'mysql') {
            $properties['propel']['generator']['platformClass'] = MysqlPlatform::class;
        }

        if (null === $input) {
            return new GeneratorConfig(null, $properties);
        }



        if ($this->hasInputOption('platform', $input)) {
            $properties['propel']['generator']['platformClass'] = $input->getOption('platform');
        }


        $properties['propel']['generator']['objectModel']['entityNotFoundExceptionClass'] = EntityNotFoundException::class;

        return new GeneratorConfig(null, $properties);
    }
}