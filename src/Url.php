<?php

namespace AMQPLib;

class Url
{
    const DEFAULT_SCHEMA = 'amqp';
    const DEFAULT_HOST = 'locahost';
    const DEFAULT_PORT = 5672;
    const DEFAULT_USER = 'guest';
    const DEFAULT_PASS = 'guest';
    const DEFAULT_VHOST = '/';

    /**
     * @var string
     */
    private $schema;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $pass;

    /**
     * @var string
     */
    private $vhost;

    /**
     * @param string $schema
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $pass
     * @param string $vhost
     */
    public function __construct($schema = null, $host = null, $port = null, $user = null, $pass = null, $vhost = null)
    {
        $this->schema = empty($schema) ? self::DEFAULT_SCHEMA : $schema;
        $this->host = empty($host) ? self::DEFAULT_HOST : $host;
        $this->port = empty($port) ? self::DEFAULT_PORT : $port;
        $this->user = empty($user) ? self::DEFAULT_USER : $user;
        $this->pass = empty($pass) ? self::DEFAULT_PASS : $pass;
        $this->vhost = empty($vhost) ? self::DEFAULT_VHOST : $vhost;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @return string
     */
    public function getVhost()
    {
        return $this->vhost;
    }

    /**
     * @param string $url
     *
     * @return Url
     *
     * @throws \Exception
     */
    public static function parse($url)
    {
        if (($parts = parse_url($url)) === false) {
            throw new \InvalidArgumentException(sprintf('Invalid URL "%s"', $url));
        }

        return new self(
            isset($parts['schema']) ? $parts['schema'] : null,
            isset($parts['host']) ? $parts['host'] : null,
            isset($parts['port']) ? $parts['port'] : null,
            isset($parts['user']) ? $parts['user'] : null,
            isset($parts['pass']) ? $parts['pass'] : null,
            isset($parts['path']) ? urldecode(substr($parts['path'], 1)) : null
        );
    }

    /**
     * @return string
     */
    public function compose()
    {
        return sprintf(
            '%s://%s:%s@%s:%s/%s',
            urlencode($this->schema),
            urlencode($this->user),
            urlencode($this->pass),
            urlencode($this->host),
            urlencode($this->port),
            urlencode($this->vhost)
        );
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->compose();
    }
}
