<?php

namespace project5\Form\Validator;

use project5\Form\IValidator;
use StasDovgodko\Uri\Url;
use InvalidArgumentException;

class Site implements IValidator
{
    protected $dnsProbe;

    public function __construct($dnsProbe = false)
    {
        $this->dnsProbe = $dnsProbe;
    }

    /**
     * @param array $params
     * @return string
     */
    public function getErrorDefaultMessage(array &$params = [])
    {
        return 'Wrong url';
    }

    /**
     * @throw InvalidArgumentException
     * @param $value
     * @return Url
     */
    public static function NormalizeUrl($value)
    {
        $is_valid_domain_name = function ($domain_name)
        {
            return (preg_match("/^([a-z\\d](-*[a-z\\d])*)(\\.([a-z\\d](-*[a-z\\d])*))*$/i", $domain_name) //valid chars check
                && preg_match("/^.{1,253}$/", $domain_name) //overall length check
                && preg_match("/^[^\\.]{1,63}(\\.[^\\.]{1,63})*$/", $domain_name)   ); //length of each label
        };

        if ($is_valid_domain_name($value)) { // just host, assume that http
            $url = new Url('http://'.$value);
        } else {

            $url = new Url($value);
            if ($url->isAbsolute() && in_array(strtolower($url->getScheme()), ['http', 'https'])) {
                // check domain
                if (!$is_valid_domain_name($url->getHost())) {
                    throw new InvalidArgumentException('Wrong http(s) url');
                }
            } else {
                throw new InvalidArgumentException('Wrong http(s) url');
            }
        }

        if (filter_var($url->__toString(), FILTER_VALIDATE_URL)) {

            return $url;
        } else {
            throw new InvalidArgumentException('Wrong http(s) url');
        }
    }

    /**
     * @param $value
     * @param string $message
     * @return boolean True if valid
     */
    public function validate($value, &$message = null)
    {
        try {
            $url = self::NormalizeUrl($value);

            if ($this->dnsProbe) {
                return checkdnsrr($url->getHost() , "A");
            }

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}