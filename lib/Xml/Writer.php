<?php
    namespace project5\Xml;

    use XMLReader, XMLWriter;
    use project5\IInProgress;

    class Writer implements IInProgress
    {
        private $_filename;

        /**
         * @var XMLReader
         */
        private $_inXml;

        /**
         * @var XMLWriter
         */
        private $_outXml;

        private $_callbacks = array();
        private $_iterate = array();

        private $_pos = 0;
        private $_posMax = 0;

        function __construct($filename, $template = null) {
            $this->_filename = $filename;


            if ($template) { // autoload Reader to ensure that filter initiated

                if (is_file($template)) {
                    $size = filesize($template);
                    $file = $template;//'php://filter/read=pos.'.$id.'/resource='.$template;

                    $this->_inXml = new XMLReader();
                    $this->_inXml->open($file, 'UTF-8', LIBXML_NOWARNING );
                } else {
                    $this->_inXml = new XMLReader();
                    $this->_inXml->xml($template, 'UTF-8', LIBXML_NOWARNING );
                    $size = strlen($template);
                }
                $this->_posMax = $size;
            }

            $this->_outXml = new XMLWriter();

            $this->_outXml->openMemory();// openUri($filename);
            $this->_outXml->setIndent(true);
            $this->_outXml->startDocument();

        }

        function inject($path, $callback)
        {
            if (!isset($this->_callbacks[$path])) {
                $this->_callbacks[$path] = array();
            }
            $this->_callbacks[$path][] = array($callback, null);

            return $this;
        }

        function iterate($path, $callback, $iterator)
        {
            assert('is_int($iterator) || is_array( $iterator ) || $iterator instanceof \Traversable');

            if (is_int($iterator)) {

                $generator = function($iterator) {
                    for($i=0; $i<$iterator; $i++) {
                        yield $i;
                    }
                };
                $iterator = $generator($iterator);
            }

            if (!isset($this->_callbacks[$path])) {
                $this->_callbacks[$path] = array();
            }
            $this->_callbacks[$path][] = array($callback, $iterator);

            return $this;
        }


        /**
         * @return int
         */
        public function getProgress()
        {
            return $this->_pos;
        }

        /**
         * @return int|null
         */
        public function getProgressMax()
        {
            return max($this->_pos, $this->_posMax);
        }

        private $_path = array();
        /**
         * @return bool
         */
        public function progress()
        {
            if ($this->_inXml) {
                $valid = $this->_writeReader($this->_inXml, true);

            } else return false;

            return $valid;
        }

        private function _writeReader($reader, $is_template = false)
        {
            $valid = $reader->read();



            $writer = $this->_outXml;

            $flush = function() use($writer, $is_template) {
                $b = $writer->flush(false); if (is_string($b)) $b = strlen($b);

                $this->_pos += $b;
                if (!$is_template) $this->_posMax += $b;

            };

            if ($valid)
            {
                $node_type = $reader->nodeType;

                if ($node_type === XMLReader::ELEMENT) {
                    $name = ($reader->namespaceURI) ? ($reader->namespaceURI . ':' . $reader->localName) : $reader->localName;

                    $this->_path[] = $name;


                    $start_element = function() use($reader, $writer, $flush, $is_template) {
                        if ($reader->namespaceURI) {
                            $writer->startElementNs($reader->prefix, $reader->name, $reader->namespaceURI);
                        } else $writer->startElement($reader->name);

                        if (!$reader->depth && $reader->xmlLang) {
                            $writer->writeAttributeNs('xml', 'lang', Reader::XML_NS, $reader->xmlLang);
                        }

                        while($reader->moveToNextAttribute()) {
                            if ($reader->namespaceURI) {
                                $writer->writeAttributeNs($reader->prefix, $reader->name, $reader->value, $reader->namespaceURI);
                            } else $writer->writeAttribute($reader->name, $reader->value);


                            $flush();
                        }
                    };

                    $end_element = function() use($writer, $flush) {
                        $writer->endElement();
                        $flush();
                    };

                    $spath = implode('/', $this->_path);

                    if (isset($this->_callbacks[$spath])) {

                        foreach($this->_callbacks[$spath] as list($callback, $iterator)) {

                            $return = call_user_func_array($callback, array($this));

                            if ($return instanceof \Iterator) {
                                while($return->valid()) {
                                    //$start_element($is_template);
                                    call_user_func_array($callback, array($this));
                                    //$end_element();
                                    $flush();
                                }
                            }

                            /*
                            if ($iterator === null) {
                                $start_element();




                                //if ()

                                $end_element();
                            } else {
                                foreach($iterator as $step) {
                                    //$start_element($is_template);
                                    call_user_func_array($callback, array($this, $step));
                                    //$end_element();
                                    $flush();
                                }
                            }
*/
                        }

                        //$writer->endElement();
                        $reader->next();


                        array_pop($this->_path);
                    } else {
                        $start_element($is_template);
                    }

                    $flush();
                } elseif ($node_type == XMLReader::CDATA) {
                    $writer->writeCdata($reader->value);
                    $b = $writer->flush(false); if (is_string($b)) $b = strlen($b);

                    $this->_pos += $b;
                    if (!$is_template) $this->_posMax += $b;
                } elseif ($node_type == XMLReader::COMMENT) {
                    $writer->writeComment($reader->value);
                    $flush();
                } elseif ($node_type == XMLReader::TEXT) {
                    $writer->text($reader->value);
                    $flush();
                } elseif ($node_type == XMLReader::END_ELEMENT) {
                    $writer->endElement();
                    $flush();

                    array_pop($this->_path);
                }
            }

            $flush();

            return $valid;
        }

        public function writeXml($xml)
        {
            $reader = new XMLReader();
            $reader->xml($xml);

            while ($this->_writeReader($reader)) {

            }
        }

        public function writeFile($uri)
        {
            $reader = new XMLReader();
            $reader->open($uri);

            while ($this->_writeReader($reader)) {

            }
        }

        public function end()
        {
            $this->_outXml->endDocument();
            //$this->_outXml->flush();
            return 'e'.$this->_outXml->outputMemory();
        }
    }