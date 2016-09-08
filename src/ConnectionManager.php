<?php

namespace ButterAMQP;

use ButterAMQP\IO\StreamIO;

class ConnectionManager
{
    /**
     * @param Url|string|array $url
     *
     * @return ConnectionInterface
     */
    public static function connect($url)
    {
        if (is_string($url)) {
            $url = Url::parse($url);
        }

        if (is_array($url)) {
            $url = Url::import($url);
        }

        if (!$url instanceof Url) {
            throw new \InvalidArgumentException(sprintf('URL should be a string, an array or an instance of Url class'));
        }

        return new Connection($url, new Wire(new StreamIO()));
    }
}
