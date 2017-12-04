<?php
namespace project5\Stream;

class String implements IStreamable
{
    protected $data;
    protected $length;
    protected $_pointer = 0;

    public function __construct($data = '')
    {
        $this->data = $data;
        $this->length = strlen($data);
        $this->_pointer = $this->length;
    }

    /**
     * @return string
     */
    public function hashCode()
    {
        return spl_object_hash($this);
    }

    public function close()
    {
        $this->data = null;
        $this->length = 0;
    }

    public function detach()
    {
        if ($this->data !== null) {
            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $this->data);
            fseek($handle, 0);
            $this->close();
            return $handle;
        }
        return null;
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will return FALSE, indicating
     * failure; otherwise, it will perform a seek(0), and return the status of
     * that operation.
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function rewind()
    {
        $this->_pointer = 0;

        return true;
    }

    public function getSize()
    {
        return $this->length;
    }

    public function tell()
    {
        return $this->_pointer;
    }

    public function eof()
    {
        if ($this->data !== null) {
            return $this->_pointer >= $this->length;
        }
        return true;
    }

    public function isSeekable()
    {
        return $this->data !== null;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->isSeekable()) {
            if ($whence === SEEK_SET) {
                $this->_pointer = $offset;
            } else {
                if ($whence === SEEK_CUR) {
                    $this->_pointer += $offset;
                } else {
                    if ($whence === SEEK_END) {
                        $this->_pointer = $this->length + $offset;
                    }
                }
            }
        }
        return false;
    }

    public function isWritable()
    {
        return $this->data !== null;
    }

    public function write($string)
    {
        if ($this->isWritable()) {
            $length = strlen($string);
            $pre = substr($this->data, 0, $this->_pointer);
            $post = substr($this->data, $this->_pointer + $length);
            $this->data = $pre . $string . $post;
            $this->length += $length;
            $this->_pointer += $length;
            return $length;
        }
        return false;
    }

    public function isReadable()
    {
        return $this->data !== null;
    }

    public function read($maxLength)
    {
        if ($this->isReadable()) {
            $data = substr($this->data, $this->_pointer, $maxLength);
            $this->_pointer += $maxLength;
            return $data;
        }
        return false;
    }

    public function getContents()
    {
        return $this->data;
    }

    public function getMetadata($key = null)
    {
        return null;
    }

    public function __toString()
    {
        return $this->data === null ? '' : (string)$this->data;
    }
}