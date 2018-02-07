<?php
    namespace project5\Form;

    class DateTimeZone extends Select {
        /**
         * @var \DateTime
         */
        private $hashSince, $hashTill;

        protected $hashes = [];

        public function __construct($name, $title = null, $maxlimit = 5, \DateTime $groupSince = null, \DateTime $groupTill = null)
        {
            $this->hashSince = $groupSince ? $groupSince : new \DateTime('-1 year', new \DateTimeZone('UTC'));
            $this->hashTill = $groupTill ? $groupTill : new \DateTime('+5 years', new \DateTimeZone('UTC'));


            $hashes = []; $groups = [];
            foreach (\DateTimeZone::listIdentifiers() as $tzid) {
                $tz = new \DateTimeZone($tzid);
                $hash = $this->hash($tz);


                if (array_key_exists($hash, $groups)) {
                    $groups[$hash]++;
                } else $groups[$hash] = 1;

                $k = ceil($groups[$hash] / $maxlimit);

                if (array_key_exists($hash.$k, $this->hashes)) {
                    $this->hashes[$hash.$k][] = $tz;
                } else {
                    $this->hashes[$hash.$k] = [$tz];
                }
            }

            parent::__construct($name, [], $title);
        }
        
        public function getTimezones()
        {
            $list = [];
            foreach($this->hashes as $hash => $timezones) {

                $first_tz = $timezones[0]; /** @var \DateTimeZone $first_tz */
                
                foreach($timezones as $tz) {
                    $list[$tz->getName()] = $first_tz->getName();
                }
            }
            
            return $list;
        }


        protected function hash(\DateTimeZone $tz)
        {
            return md5(serialize($tz->getTransitions($this->hashSince->getTimestamp(), $this->hashTill->getTimestamp())));
        }

        public function getOptions()
        {


            $now_utc = new \DateTime('now', new \DateTimeZone('UTC'));
            foreach($this->hashes as $hash => $timezones) {

                $first_tz = $timezones[0]; /** @var \DateTimeZone $first_tz */
                $offset = $first_tz->getOffset($now_utc);

                if ($offset === 0) {
                    $offset_text = '';
                } else {
                    $h = floor($offset / 3600);
                    $offset_text = (($offset > 0)?'+':'')
                        .str_pad($h, 2, '0', STR_PAD_LEFT).':'
                        .str_pad(($offset - ($h *  3600))/60, 2, '0', STR_PAD_LEFT);
                }

                $cities = [];

                foreach ($timezones as $tz) {
                    $tzid = $tz->getName();
                    if (strpos($tzid, '/')) {
                        $parts = explode('/', $tzid);
                        if (count($parts) == 3) {
                            list($m, $country, $city) = $parts;
                        } elseif (count($parts) == 2) {
                            list($country, $city) = $parts;
                        } else {
                            $city = $tzid;
                        }

                    } else {
                        $city = $tzid;
                    }

                    $cities[] = str_replace('_', ' ', $city);
                }

                $values[$hash] = [$offset, '(UTC'.$offset_text.') '.implode(', ', $cities), $timezones[0]];
            }
            uasort($values, function($a, $b) {
                if ($a[0] == $b[0]) {
                    return 0;
                }
                return ($a[0] < $b[0]) ? -1 : 1;
            });

            $options = [];
            foreach ($values as $hash => $info) {
                $options[$info[2]->getName()] = $info[1];
            }




            return $options;
        }
    }