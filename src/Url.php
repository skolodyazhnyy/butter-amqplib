<?php

namespace ButterAMQP;

class Url
{
    const DEFAULT_SCHEMA = 'amqp';
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 5672;
    const DEFAULT_SECURE_PORT = 5671;
    const DEFAULT_USER = 'guest';
    const DEFAULT_PASS = 'guest';
    const DEFAULT_VHOST = '/';

    /**
     * @var string
     */
    private $scheme;

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
     * @var array
     */
    private $query = [];

    /**
     * @param string $scheme
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $pass
     * @param string $vhost
     * @param array  $query
     */
    public function __construct(
        $scheme = null,
        $host = null,
        $port = null,
        $user = null,
        $pass = null,
        $vhost = null,
        array $query = []
    ) {
        if (empty($port)) {
            $port = strcasecmp($scheme, 'amqps') == 0 ? self::DEFAULT_SECURE_PORT : self::DEFAULT_PORT;
        }

        $this->scheme = $scheme === null ? self::DEFAULT_SCHEMA : $scheme;
        $this->host = $host === null ? self::DEFAULT_HOST : $host;
        $this->port = $port;
        $this->user = $user === null ? self::DEFAULT_USER : $user;
        $this->pass = $pass === null ? self::DEFAULT_PASS : $pass;
        $this->vhost = empty($vhost) ? self::DEFAULT_VHOST : $vhost;
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
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
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getQueryParameter($key, $default = null)
    {
        return isset($this->query[$key]) ? $this->query[$key] : $default;
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

        $query = [];

        parse_str(isset($parts['query']) ? $parts['query'] : '', $query);

        return new self(
            isset($parts['scheme']) ? $parts['scheme'] : null,
            isset($parts['host']) ? $parts['host'] : null,
            isset($parts['port']) ? (int) $parts['port'] : null,
            isset($parts['user']) ? urldecode($parts['user']) : null,
            isset($parts['pass']) ? urldecode($parts['pass']) : null,
            isset($parts['path']) ? urldecode(substr($parts['path'], 1)) : null,
            $query
        );
    }

    /**
     * @param bool $maskPassword
     *
     * @return string
     */
    public function compose($maskPassword = false)
    {
        return sprintf(
            '%s://%s:%s@%s:%d/%s%s',
            urlencode($this->scheme),
            urlencode($this->user),
            $maskPassword ? '******' : urlencode($this->pass),
            urlencode($this->host),
            $this->port,
            urlencode($this->vhost),
            $this->query ? '?'.http_build_query($this->query) : ''
        );
    }

    /**
     * Import URL from an array.
     *
     * @param array $data
     *
     * @return Url
     */
    public static function import(array $data)
    {
        return new self(
            isset($data['scheme']) ? $data['scheme'] : null,
            isset($data['host']) ? $data['host'] : null,
            isset($data['port']) ? (int) $data['port'] : null,
            isset($data['user']) ? $data['user'] : null,
            isset($data['password']) ? $data['password'] : null,
            isset($data['vhost']) ? $data['vhost'] : null,
            isset($data['parameters']) ? (array) $data['parameters'] : []
        );
    }

    /**
     * Export URL to an array.
     *
     * @return array
     */
    public function export()
    {
        return [
            'scheme' => $this->scheme,
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'password' => $this->pass,
            'vhost' => $this->vhost,
            'parameters' => $this->query,
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->compose(true);
    }
}
