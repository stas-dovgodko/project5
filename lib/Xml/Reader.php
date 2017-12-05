<?php
    namespace project5\Xml;

    use StasDovgodko\Uri;
    use XMLReader;

    use project5\IInProgress;

    class position_filter extends \php_user_filter
    {
        private $id;
        public static $POS = array();
        function filter($in, $out, &$consumed, $closing)
        {


            while ($bucket = stream_bucket_make_writeable($in)) {
                $bucket->data = ($bucket->data);
                $consumed += $bucket->datalen;
                if (!isset(self::$POS[$this->id])) {
                    self::$POS[$this->id] = 0;
                }
                self::$POS[$this->id] += $bucket->datalen;

                stream_bucket_append($out, $bucket);
            }
            return PSFS_PASS_ON;
        }

        public function onCreate () {
            list($prefix, $id) = explode('.', $this->filtername);
            $this->id = $id;
        }
    }
    stream_filter_register('pos.*', 'XML\position_filter');


    class Reader implements IInProgress {
        const XML_NS = 'http://www.w3.org/XML/1998/namespace';

        const CALLBACK_TYPE_TREE = 1;
        const CALLBACK_TYPE_ELEMENT = 2;
        const CALLBACK_TYPE_DOM = 3;
        const CALLBACK_TYPE_FILE = 4;

        const CALLBACK_TYPE_SIBLING = 5;

        /**
         * @var \XMLReader
         */
        private $_reader;

        private $_id;
        private $_posIdx;

        private $_callbacks = array();

        private $_clones = array();

        private $_tmpFile;
        private $_filename;

        private $_seekIdx;

        private static $_TmpFilesPool = array();


        protected function __construct(XMLReader $reader, $filename = null) {
            $this->_reader = $reader;
            $this->_filename = $filename;
        }

        public function getFilename(){
            return $this->_filename;
        }

        function findTree($path, callable $callback, callable $filter = null)
        {
            if (!isset($this->_callbacks[$path])) {
                $this->_callbacks[$path] = array();
            }
            $this->_callbacks[$path][] = array(self::CALLBACK_TYPE_TREE, $callback, $filter);

            return $this;
        }

        function findElement($path, callable $callback, callable $filter = null)
        {
            if (!isset($this->_callbacks[$path])) {
                $this->_callbacks[$path] = array();
            }
            $this->_callbacks[$path][] = array(self::CALLBACK_TYPE_ELEMENT, $callback, $filter);

            return $this;
        }

        function findXml($path, callable $callback, callable $filter = null)
        {
            if (!isset($this->_callbacks[$path])) {
                $this->_callbacks[$path] = array();
            }
            $this->_callbacks[$path][] = array(self::CALLBACK_TYPE_FILE, $callback, $filter);

            return $this;
        }

        function findSibling($path, callable $callback, callable $filter = null)
        {
            if (!isset($this->_callbacks[$path])) {
                $this->_callbacks[$path] = array();
            }
            $this->_callbacks[$path][] = array(self::CALLBACK_TYPE_SIBLING, $callback, $filter);

            return $this;
        }

        function findDom($path, callable $callback, callable $filter = null)
        {
            if (!isset($this->_callbacks[$path])) {
                $this->_callbacks[$path] = array();
            }
            $this->_callbacks[$path][] = array(self::CALLBACK_TYPE_DOM, $callback, $filter);

            return $this;
        }

        static function _GetNextTmpFilename()
        {
            foreach(self::$_TmpFilesPool as $filename => $lock)
            {
                if (!$lock) return $filename;
            }
            $filename = tempnam("/tmp", "xml".getmypid());

            self::$_TmpFilesPool[$filename] = true;

            return $filename;
        }

        public function __destruct()
        {
            if ($this->_tmpFile && is_file($this->_tmpFile)) {
                if (isset(self::$_TmpFilesPool[$this->_tmpFile])) self::$_TmpFilesPool[$this->_tmpFile] = 0;
                unlink($this->_tmpFile);
            }
            $this->_callbacks = array();
            $this->_clones = array();

            $this->_reader->close();

            unset($this->_reader);

            $this->_reader = 1;
            unset($this->_reader);

        }

        public static function File($file)
        {
            $id = md5(microtime(true).md5($file).rand(0,10000));
            position_filter::$POS[$id] = 0;

            $size = @filesize($file);
            $filterfile = $file;//'php://filter/read=pos.'.$id.'/resource='.$file;

            $xml = new XMLReader();

            $xml->open($filterfile, 'UTF-8', LIBXML_XINCLUDE );
            //$xml->baseURI = $file;
            $reader = new Reader($xml, $file);



            $reader->_progressMax = $size;
            $reader->_id = $id;
            $reader->_posIdx = 0;

            return $reader;
        }

        public function getAbsoluteUri($relativeUri)
        {
            $uri = new Uri($this->_reader->baseURI);
            $uri = $uri->resolve(new Uri($relativeUri));

            return (string)$uri;
        }

        private static function _DumpReader($filename, XMLReader $reader)
        {
            static $writer;

            if (!$writer) {
                $writer = new \XMLWriter();
            }

            $writer->openUri($filename);
            $writer->setIndent(true);
            $writer->startDocument();


            $initial_deep = $reader->depth;

            do {
                $node_type = $reader->nodeType;
                if ($node_type == XMLReader::ELEMENT) {



                    if (false && $reader->isEmptyElement) {
                        if ($reader->namespaceURI) {
                            $writer->writeElementNs($reader->prefix, $reader->name, $reader->namespaceURI);
                        } else $writer->writeElement($reader->name);
                    } else {
                        if ($reader->namespaceURI) {
                            $writer->startElementNs($reader->prefix, $reader->name, $reader->namespaceURI);
                        } else $writer->startElement($reader->name);
                    }

                    $is_empty = $reader->isEmptyElement;

                    $existed = array();
                    if ($reader->depth == $initial_deep) {
                        if ($reader->xmlLang) {
                            $writer->writeAttributeNs('xml', 'lang', self::XML_NS, $reader->xmlLang);
                            $existed[] = self::XML_NS . ':lang';
                        }
                        if ($reader->baseURI) {
                            $writer->writeAttributeNs('xml', 'base', self::XML_NS, $reader->baseURI);
                            $existed[] = self::XML_NS . ':base';
                        }
                    }

                    while($reader->moveToNextAttribute()) {
                        if ($reader->namespaceURI) {
                            if ($reader->namespaceURI !== 'http://www.w3.org/2000/xmlns/') {
                            $fullname = $reader->namespaceURI . ':' . $reader->localName;
                            if (!in_array($fullname, $existed)) {
                                $writer->writeAttributeNs($reader->prefix, $reader->localName, $reader->namespaceURI, $reader->value);
                                $existed[] = $fullname;
                            }}
                        } elseif (!in_array($reader->localName, $existed)) {
                            $writer->writeAttribute($reader->localName, $reader->value);
                            $existed[] = $reader->name;
                        }
                        $writer->flush(false);
                    }


                    if ($is_empty) {
                        $writer->endElement();
                    }
                    $writer->flush(false);
                } elseif ($node_type == XMLReader::CDATA) {
                    $writer->writeCdata($reader->value);
                    $writer->flush(false);
                } elseif ($node_type == XMLReader::COMMENT) {
                    $writer->writeComment($reader->value);
                    $writer->flush(false);
                } elseif ($node_type == XMLReader::TEXT) {
                    $writer->text($reader->value);
                    $writer->flush(false);
                } elseif ($node_type == XMLReader::END_ELEMENT) {
                    $writer->endElement();
                    $writer->flush(false);
                }
            } while(($reader->depth > $initial_deep) && $reader->read());
            $writer->endDocument();
            $writer->flush();
        }

        private function _cloneReaderTree()
        {
            $reader = self::File($file = $this->_dumpReaderTree());
            $reader->_tmpFile = $file;


            $this->_clones[$reader->_id] = $reader->_progressMax;

            return $reader;
        }

        private function _dumpReaderTree()
        {
            $tmpfname = self::_GetNextTmpFilename();

            self::_DumpReader($tmpfname, $this->_reader);

            return $tmpfname;
        }

        private $_progressMax = 0;
        private $_path = array();

        public function progress()
        {
            $valid = $this->_reader->read();

            if ($valid)
            {
                $found = ($this->_seekIdx <= $this->_posIdx);

                if ($this->_reader->nodeType == XMLReader::ELEMENT) {

                    $name = ($this->_reader->namespaceURI) ? ($this->_reader->namespaceURI . ':' . $this->_reader->localName) : $this->_reader->localName;

                    $this->_path[] = $name;

                    $spath = implode('/', $this->_path);

                    $close = false;

                    if (isset($this->_callbacks[$spath])) {

                        foreach($this->_callbacks[$spath] as list($callback_type, $callback, $filter)) {

                            /** @var $callback callable */
                            $attrs = array();
                            while($this->_reader->moveToNextAttribute()) {
                                if ($this->_reader->namespaceURI) {
                                    $attrs[$this->_reader->namespaceURI . ':' . $this->_reader->localName] = $this->_reader->value;
                                } else {
                                    $attrs[$this->_reader->localName] = $this->_reader->value;
                                }
                            }
                            $this->_reader->moveToElement();

                            if (($filter === null) || call_user_func_array($filter, array($attrs))) {

                                switch ($callback_type) {
                                    case self::CALLBACK_TYPE_TREE:
                                        if ($found) call_user_func_array($callback, array($this->_cloneReaderTree(), $attrs));
                                        $this->_reader->next();
                                        $close = true;
                                        break;
                                    case self::CALLBACK_TYPE_DOM:
                                        if ($found) {
                                            call_user_func_array($callback, array($this->_reader->expand(), $attrs));
                                        } else {
                                            $this->_reader->next();
                                            $close = true;
                                        }


                                        break;
                                    case self::CALLBACK_TYPE_FILE:
                                        if ($found) call_user_func_array($callback, array($this->_dumpReaderTree(), $attrs));
                                        $this->_reader->next();
                                        $close = true;
                                        break;
                                    case self::CALLBACK_TYPE_SIBLING:
                                        if ($found) {
                                            $parent_deep = $this->_reader->depth;
                                            $files = array();
                                            while($this->_reader->read() && $this->_reader->depth > $parent_deep) {
                                                if ($this->_reader->depth > $parent_deep && $this->_reader->nodeType === XMLReader::ELEMENT) {
                                                $files[] = $this->_dumpReaderTree();

                                                    $this->_reader->next();
                                                }
                                                ;
                                            }
                                            call_user_func_array($callback, array($files, $attrs));
                                        }
                                        //$this->_reader->next();
                                        $close = true;
                                        break;
                                    case self::CALLBACK_TYPE_ELEMENT:
                                        if ($found) call_user_func_array($callback, array($name, $attrs, $this->_reader->readString()));
                                        break;
                                }
                            } else {
                                $this->_reader->next();
                                $close = true;
                            }
                        }

                    }

                    if ($close || $this->_reader->isEmptyElement) {
                        array_pop($this->_path);
                        $this->_posIdx++;
                    }


                } elseif ($this->_reader->nodeType == XMLReader::END_ELEMENT) {
                    array_pop($this->_path);
                    $this->_posIdx++;
                }
                $this->_posIdx++;

            }

            return $valid;
        }

        /**
         * @return int
         */
        public function getProgress()
        {
            $progress = isset(position_filter::$POS[$this->_id]) ? position_filter::$POS[$this->_id] : 0;

            foreach($this->_clones as $id => $max) {
                $progress += isset(position_filter::$POS[$id]) ? position_filter::$POS[$id] : 0;
            }

            return $progress;
        }

        /**
         * @return int|null
         */
        public function getProgressMax()
        {
            $progress = $this->_progressMax;

            foreach($this->_clones as $max) {
                $progress += $max;
            }

            return $progress;
        }

        public function getCursor()
        {
            return $this->getPosIdx();
        }

        public function setCursor($cursor)
        {
            $this->_seekIdx = $cursor;
            return;
        }

        public function getPosIdx()
        {
            return $this->_posIdx;
        }
    }