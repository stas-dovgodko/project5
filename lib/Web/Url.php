<?php
namespace project5\Web;

use project5\Uri;

use Psr\Http\Message\UriInterface;

/**
 * Represents a Uniform Resource Identifier (URI) reference.
 *
 * Aside from some minor deviations noted below, an instance of this class represents a URI reference
 * as defined by RFC 2396: Uniform Resource Identifiers (URI): Generic Syntax, amended by RFC 2732: Format for Literal IPv6 Addresses in URLs.
 * This class provides constructor for creating URI instances from their string forms, methods for accessing the various components of an instance,
 * and methods for normalizing and resolving URI instances.
 * Instances of this class are immutable.
 *
 */
class Url extends Uri implements UriInterface
{
    /**
     * Push value to query encoded
     *
     * @param string $name
     * @param string $value
     * @return Url
     */
    public function withQueryValue($name, $value)
    {
        $uri = clone $this;
        parse_str($this->query, $data);
        $data[$name] = $value;

        $uri->query = http_build_query($data);

        return $uri;
    }

    /**
     * @param string $name Slug name
     * @param string $value Slug candidate value. Will contain filtered (URL-ready) value after
     * @param callable $test callback to make unique value. Should return true for "bad" value
     * @return Url
     */
    public function withQuerySlug($name, &$value, callable $test = null)
    {
        $value = \URLify::filter($value);

        if (is_callable($test)) {
            $i = 1; $base = $value;
            while(call_user_func($test, $value)) {
                $i+=rand(1, 99);
                $value = $base.'-'.$i;
            }
        }

        return $this->withQueryValue($name, $value);
    }

    /**
     * Push values to query encoded
     *
     * @param array $values
     * @return Url
     */
    public function withQueryValues(array $values)
    {

        if ($this->query !== null) {
            parse_str($this->query, $data);
        } else {
            $data = [];
        }

        $uri = clone $this;
        $uri->query = http_build_query(array_merge($data, $values));

        return $uri;
    }
}
