<?php
namespace project5\Xml;

use XMLReader;
use XMLWriter;

require_once __DIR__ . '/Reader_gist.php';

class Render
{
    private $callbacks = [];

    public function __construct()
    {

    }

    public function handleElementXpath($xpath, $callable)
    {
        $this->callbacks[] = ['element_xpath', $xpath, $callable];

        return $this;
    }


    public function render(\XMLReader $reader, \XMLWriter $writer)
    {


        $it = new \XMLReaderIterator($reader);

        $element_filters = [];
        foreach($this->callbacks as $info) {

            list($type, $xpath, $callable) = $info;

            if ($type === 'element_xpath') {
                $element_filters[] = [new \XMLElementXpathFilter($it, $xpath), $callable];
            }


        }

        $it->rewind();

        while($it->valid()) {

            $node_type = $it->current()->nodeType;

            $pointed = false;

            if ($node_type === XMLReader::ELEMENT) {
                $writer->startElement($reader->name);

                foreach($element_filters as $info) {

                    list($filter, $callable) = $info;

                    if ($filter->accept()) {
                        $pointed = true;
                        if (is_callable($callable)) {
                            $return = call_user_func($callable, $writer);

                            if (is_array($return) || $return instanceof \Traversable) {
                                foreach ($return as $step) {
                                    // iterate

                                }
                            } elseif ($return !== null) {
                                $writer->text($return);
                            }
                        } else {
                            $writer->text($callable);
                        }
                    }
                }

                if ($reader->moveToFirstAttribute()) {
                    do {
                        $writer->writeAttribute($reader->name, $reader->value);
                    } while ($reader->moveToNextAttribute());
                    $reader->moveToElement();
                }



                if ($pointed) {
                    //if ($reader->isEmptyElement) {
                        $writer->endElement();
                    //}
                    $it->moveToNextSiblingElement();
                }

            } elseif ($node_type == XMLReader::CDATA) {
                $writer->writeCdata($reader->value);
            } elseif ($node_type == XMLReader::COMMENT) {
                $writer->writeComment($reader->value);
            } elseif ($node_type == XMLReader::TEXT) {
                $writer->text($reader->value);
            } elseif ($node_type == XMLReader::END_ELEMENT) {
                $writer->endElement();
            }



            if (!$pointed) {
                $it->next();
            }

            $writer->flush(true);


        }



        /*



        $i = 0;
        while($reader->read()) {

            foreach($this->callbacks as $info) {

                list($xpath, $callable) = $info;

                $filter = new XMLElementXpathFilter($it, $xpath);
                if ($filter->accept()) {

                }
            }


            //return !($result[0]->children()->count());



        }*/
    }
}