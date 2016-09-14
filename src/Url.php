<?php

namespace ButterAMQP;

class Url
{
    const DEFAULT_SCHEMA = 'amqp';
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 5672;
    const DEFAULT_SECURE_PORT = 5671;
    const DEFAULT_USER = 'guest';
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
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

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
     * @param int    $port
     * @param string $user
     * @param string $password
     * @param string $vhost
     * @param array  $query
     */
    public function __construct(
        $scheme = null,
        $host = null,
        $port = null,
        $user = null,
        $password = null,
        $vhost = null,
        array $query = []
    ) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme ?: self::DEFAULT_SCHEMA;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host ?: self::DEFAULT_HOST;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        if (empty($this->port)) {
            return strcasecmp($this->getScheme(), 'amqps') == 0 ? self::DEFAULT_SECURE_PORT : self::DEFAULT_PORT;
        }

        return $this->port;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user === null ? self::DEFAULT_USER : $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getVhost()
    {
        return $this->vhost ?: self::DEFAULT_VHOST;
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

        $parts = array_merge(
            [
                'scheme' => null,
                'host' => null,
                'port' => null,
                'user' => null,
                'pass' => null,
                'path' => null,
                'query' => '',
            ],
            $parts
        );

        parse_str($parts['query'], $query);

        return new self(
            urldecode($parts['scheme']),
            urldecode($parts['host']),
            $parts['port'],
            urldecode($parts['user']),
            urldecode($parts['pass']),
            urldecode(substr($parts['path'], 1)),
            $query ?: []
        );
    }

    /**
     * @param bool $maskPassword
     *
     * @return string
     */
    public function compose($maskPassword = true)
    {
        return sprintf(
            '%s://%s:%s@%s:%d/%s%s',
            urlencode($this->scheme),
            urlencode($this->user),
            $maskPassword ? '******' : urlencode($this->password),
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
        $data = array_merge(
            [
                'scheme' => null,
                'host' => null,
                'port' => null,
                'user' => null,
                'password' => null,
                'vhost' => null,
                'parameters' => [],
            ],
            $data
        );

        return new self(
            $data['scheme'],
            $data['host'],
            (int) $data['port'],
            $data['user'],
            $data['password'],
            $data['vhost'],
            (array) $data['parameters']
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
            'password' => $this->password,
            'vhost' => $this->vhost,
            'parameters' => $this->query,
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->compose();
    }
}
