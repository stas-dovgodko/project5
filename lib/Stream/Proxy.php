<?php
namespace project5\Stream;

class Proxy
{
    static protected $streamable = [];

    /**
     * @var IStreamable
     */
    protected $proxied;



    function stream_open($path, $mode, $options, &$opened_path)
    {

        $url = parse_url($path);
        if (isset(self::$streamable[$url["host"]])) {
            $this->proxied = self::$streamable[$url["host"]]; /** @var $streamable IStreamable */

            $this->proxied->rewind();

            return true;
        } else {
            return false;
        }
    }

    public function __call($method, $args)
    {
        if ($this->proxied && substr($method,0,7) === 'stream_') {
            return call_user_func_array([$this->proxied, substr($method,7)], $args);
        }
    }

    /*function stream_read($count)
    {
        if ($this->proxied) {
            return $this->proxied->read($count);
        }
    }

    function stream_write($data)
    {
        if ($this->proxied) {
            return $this->proxied->write($data);
        }
    }



    function stream_eof() {

    }*/

    function stream_flush()
    {
        return true;
    }

    function url_stat()
    {
        return [];
    }

    function stream_stat()
    {
        return [];
        if ($this->proxied) {
            return $this->proxied->getMetadata();
        }
    }


    public static function Wrap(IStreamable $streamable)
    {
        $hash = $streamable->hashCode();
        self::$streamable[$hash] = $streamable;

        @stream_wrapper_register('project5streamproxy', self::class);

        return 'project5streamproxy://'.$hash;
    }
}