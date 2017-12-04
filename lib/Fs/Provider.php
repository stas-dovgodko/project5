<?php

    namespace project5\Fs;

    use project5\IProvider;

    use project5\Provider\Field\Enum;
    use project5\Provider\Field\Number;
    use project5\Provider\ICanBeFiltered;
    use project5\Provider\ICanBePaged;
    use project5\Provider\Methods;
    use project5\Provider\SyncIteratorMethods;
    use project5\Provider\Field\String;

    use Symfony\Component\Finder\Finder;

    class Provider implements IProvider, ICanBeFiltered, ICanBePaged
    {
        use Methods, SyncIteratorMethods;

        /**
         * @var Finder
         */
        private $_finder;

        /**
         * @var \Iterator
         */
        private $_iterator;

        private function __construct(Finder $finder)
        {
            $this->_finder = $finder;
        }

        /**
         * @param string $directory
         * @param string $pattern
         * @return Provider
         */
        public static function Scan($directory, $pattern = '*')
        {
            $provider = new Provider(Finder::create()->files()->name($pattern)->in((string)$directory));

            return $provider;
        }


        /**
         * @param Finder $finder
         * @return Provider
         */
        public static function Find(Finder $finder)
        {
            $provider = new Provider($finder);

            return $provider;
        }

        /**
         * @return Finder
         */
        public function getFinder()
        {
            return $this->_finder;
        }

        protected function _reset($cursor, array $extra = [])
        {
            $this->_iterator = $this->getFinder()->getIterator();
            $this->_iterator->rewind();



            return 0;
        }

        protected function _next(array &$extra)
        {

            if ($this->_iterator && $this->_iterator->valid()) {

                $file = $this->_iterator->current();

                $this->_iterator->next();

                if ($file instanceof \SplFileInfo) {
                    /** @var $file \SplFileInfo */
                    $entity = new File($file);

                    return $entity;
                }
            }

            return null;
        }

        /**
         * Create empty entity
         *
         * @return File
         */
        public function createEntity()
        {
            return new NewFile();
        }

        public function getFields(callable $filter = null)
        {
            return [
                new String('filename'),
                new Enum(new String('type'), [File::TYPE_FILE => 'file', File::TYPE_DIR => 'dir', File::TYPE_LINK => 'link']),
                new Number('size'),
            ];
        }

    }