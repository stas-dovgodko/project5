<?php
namespace project5\Upload;

use project5\LoggerAwareTrait;
use project5\Stream\ITransformer;
use project5\Web\Request; // ?
use StasDovgodko\Uri;
use Psr\Log\LoggerAwareInterface;
use Psr\Http\Message\UploadedFileInterface;
use Sirius\Upload\Container\ContainerInterface;

use project5\Stream\String;
use StasDovgodko\Uri\File;

class Handler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var IStorage
     */
    protected $storage;

    /**
     * @var ITransformer[]
     */
    protected $transformers;

    public function __construct(IStorage $storage)
    {
        $this->handler = new \Sirius\Upload\Handler(new _Driver($this->storage = $storage));
        //$this->handler->setOverwrite(false); // do not overwrite existing files (default behaviour)
        $this->handler->setPrefix('subdirectory/append_'); // string to be appended to the file name
        $this->handler->setAutoconfirm(false); // disable automatic confirmation (default behaviour)

// validation rules
        //$this->handler->addRule('extension', ['allowed' => 'jpg', 'jpeg', 'png'], '{label} should be a valid image (jpg, jpeg, png)', 'Profile picture');
        //$this->handler->addRule('size', ['max' => '20M'], '{label} should have less than {max}', 'Profile picture');
        //$this->handler->addRule('imageratio', ['ratio' => 1], '{label} should be a sqare image', 'Profile picture');

// file name sanitizer, if you don't like the default one which is: preg_replace('/[^A-Za-z0-9\.]+/', '_', $name))
        $this->handler->setSanitizerCallback(function($name){
            return time() . preg_replace('/[^a-z0-9\.]+/', '-', strtolower($name));
        });
    }

    /**
     * Returns a file size limit in bytes based on the PHP upload_max_filesize and post_max_size
     * WARN - does not handle front web server limits
     */
    protected function getPhpUploadMaxSize() {

        $parse_size = function($size) {
            $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
            $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
            if ($unit) {
                // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
                return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
            }
            else {
                return round($size);
            }
        };
        // Start with post_max_size.
        $max_size = $parse_size(ini_get('post_max_size'));

        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = $parse_size(ini_get('upload_max_filesize'));
        if ($upload_max > 0 && $upload_max < $max_size) {
            $max_size = $upload_max;
        }

        return $max_size;
    }



    public function limitExtensions($extensions)
    {
        $this->handler->addRule('extension', ['allowed' => implode(', ',$extensions)], 'Should be a valid image ('.implode(', ',$extensions).')');

    }

    public function limitMaxBytes($size)
    {
        $this->handler->addRule('size', ['size' => $size], 'Should have less than {size}');

    }

    public function setPrefix($prefix)
    {
        $this->handler->setPrefix($prefix);
    }


    /**
     * @param Request $request
     * @param array $errors
     * @param array $names
     * @return \Traversable
     * @throws \Exception
     */
    public function upload(Request $request, &$errors = [], array $names = [])
    {
        if ($this->logger) {
            $this->logger->warning(sprintf('PHP upload limited to %d bytes, please increase post_max_size and upload_max_filesize if is not enough', $this->getPhpUploadMaxSize()));
        }

        $files = $request->getUploadedFiles();

        try {
            foreach ($files as $name => $uploaded_file) {
                /** @var $uploaded_file UploadedFileInterface */
                if ($uploaded_file->getClientFilename() && (empty($names) || in_array($name, $names))) {

                    $stream = $uploaded_file->getStream();

                    if (count($this->transformers) > 0) {
                        foreach($this->transformers as $transformer) {

                            $this->log(sprintf('Apply %s transformer to uploaded stream', get_class($transformer)));

                            $stream = $transformer->transform($stream);
                        }
                    }

                    if ($stream instanceof \project5\Stream\File) {
                        $tmp_name = $stream->getFilename();
                    } else {
                        $tmp_name = tempnam(sys_get_temp_dir(), 'upload');
                        $file_stream = new \project5\Stream\File($tmp_name);
                        $file_stream->write($stream->getContents());

                        //$uploaded_file->moveTo($tmp_name);
                    }
                    $file_data = [
                        'name' => $uploaded_file->getClientFilename(),
                        'type' => $uploaded_file->getClientMediaType(),
                        'size' => $uploaded_file->getSize(),
                        'tmp_name' => $tmp_name,
                        'error' => $uploaded_file->getError(),
                    ];

                    $this->log(sprintf('Uploaded stream data %s', var_export($file_data, true)));


                    $file = $this->handler->process($file_data);

                    if ($file->isValid()) {
                        $resource = new Resource($this->storage, new Uri($file->name));
                        yield $name => $resource;
                    } else {
                        if (!isset($errors[$name])) {
                            $errors[$name] = [];
                        }
                        if ($file->getMessages()) {
                            foreach ($file->getMessages() as $message) {
                                /** @var $message \Sirius\Validation\ErrorMessage */
                                $errors[$name][] = (string)$message;

                                if ($this->logger) {
                                    $this->logger->error(sprintf('Upload error - %s', (string)$message));
                                }
                            }
                        } else {
                            $errors[$name][] = 'Can\'t upload';
                            if ($this->logger) {
                                $this->logger->error('Unexpected upload error');
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setTransformers(array $transformers)
    {
        $this->transformers = [];

        foreach($transformers as $transformer) {
            if ($transformer instanceof ITransformer) {
                $this->transformers[] = $transformer;
            }
        }

        return $this;
    }

    public function transform(ITransformer $transformer)
    {
        $this->transformers[] = $transformer;

        return $this;
    }

    public function getStorage()
    {
        return $this->storage;
    }
}




class _Driver implements ContainerInterface
{
    /**
     * @var IStorage
     */
    private $storage;

    public function __construct(IStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Check if the container is writable
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->storage->isWritable();
    }

    /**
     * This will check if a file is in the container
     *
     * @param string $file
     *
     * @return bool
     */
    public function has($file)
    {
        return $this->storage->has(new File($file));
    }

    /**
     * Saves the $content string as a file
     *
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    public function save($file, $content)
    {
        return $this->storage->has(new Uri($file), new String($content));
    }

    /**
     * Delete the file from the container
     *
     * @param string $file
     *
     * @return bool
     */
    public function delete($file)
    {
        return $this->storage->delete(new Uri($file));
    }

    /**
     * Moves a temporary uploaded file to a destination in the container
     *
     * @param string $localFile local path
     * @param string $destination
     *
     * @return bool
     */
    public function moveUploadedFile($localFile, $destination)
    {
        return $this->storage->moveUploadedFile(new File($localFile), new Uri($destination));
    }
}