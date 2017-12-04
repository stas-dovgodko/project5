<?php
    namespace project5\Provider;

    use Doctrine\Common\Cache\CacheProvider;

    class Index
    {
        private $_key;
        private $_cache;

        private $_cursors = [];
        private $_extra = [];

        public function __construct($key, CacheProvider $cache = null)
        {
            $this->_key = $key;
            $this->_cache = $cache;
        }

        public function __destruct()
        {
            try {
                $this->flush();
            } catch (\Exception $e) {

            }
        }

        public function getCursor($index)
        {
            $chunk_id = $this->_getChunk($index);

            if ($this->_cache && !isset($this->_cursors[$chunk_id])) {
                $cache_key = $this->_key . $chunk_id;
                $chunk_data = $this->_cache->fetch($cache_key);
                if (is_array($chunk_data)) {
                    $this->_cursors[$chunk_id] = $chunk_data;
                } else {
                    $this->_cursors[$chunk_id] = [];
                }
            }

            return isset($this->_cursors[$chunk_id][$index]) ? $this->_cursors[$chunk_id][$index] : null;
        }

        /**
         * @param $cursor
         * @return array
         */
        public function getCursorExtradata($cursor)
        {
            if (!isset($this->_extra[$cursor])) {
                $cache_key = $this->_key . $cursor.'_extra';
                $extra_data = $this->_cache->fetch($cache_key);
                if (is_array($extra_data)) {
                    $this->_extra[$cursor] = $extra_data;
                }
            }

            return isset($this->_extra[$cursor]) ? $this->_extra[$cursor] : [];
        }

        public function getPrevCursor(&$index)
        {
            $chunk_id = $this->_getChunk($index);

            while($chunk_id >= 0 && !isset($this->_cursors[$chunk_id])) {
                if ($this->_cache) {
                    $cache_key = $this->_key . $chunk_id;
                    $chunk_data = $this->_cache->fetch($cache_key);
                    if (is_array($chunk_data)) {
                        $this->_cursors[$chunk_id] = $chunk_data;
                        break;
                    }
                }

                $chunk_id--;
            }

            $cursor = null;
            if (isset($this->_cursors[$chunk_id])) {
                $found = 0;
                foreach($this->_cursors[$chunk_id] as $i => $candidate) {
                    if ($i > $found && $i <= $index) {
                        $cursor = $candidate;
                        $found = $i;
                    }
                }
                if ($found) $index = $found;
            }


            return $cursor;
        }

        public function setCursor($index, $cursor, array $extra = [])
        {
            $chunk_id = $this->_getChunk($index);

            if (!isset($this->_cursors[$chunk_id])) {
                $this->_cursors[$chunk_id] = [];
            }
            if ($extra && !isset($this->_cursors[$chunk_id][$index])) {
                $this->_extra[$cursor] = $extra;
            }
            $this->_cursors[$chunk_id][$index] = [$cursor, $extra];


        }

        public function flush()
        {
            if ($this->_cache) {
                foreach($this->_cursors as $chunk_id => $cursors) {
                    $cache_key = $this->_key . $chunk_id;
                    $this->_cache->save($cache_key, $cursors);
                    unset($this->_cursors[$chunk_id]);
                }
                foreach($this->_extra as $cursor => $extra) {
                    $cache_key = $this->_key . $cursor.'_extra';

                    $this->_cache->save($cache_key, $extra);
                    unset($this->_extra[$cursor]);
                }
            } else {
                $this->_cursors = [];
            }
        }

        private function _getChunk($index)
        {
            assert('is_numeric($index)');

            return (int)($index / 100);
        }
    }