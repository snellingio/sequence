<?php
declare(strict_types=1);

namespace Snelling\Sequence;

use RuntimeException;

class Payload
{
    private $payload = [];

    public function __call($method, $value)
    {
        $function = strtolower(substr($method, 0, 3));
        $key      = strtolower(substr($method, 3));
        if ($function === 'set') {
            $this->__set($key, $value);

            return true;
        }
        if ($function === 'get') {
            return $this->__get($key);
        }

        throw new RuntimeException('Payload method `' . $method . '` does not exist');
    }

    public function __get(string $key)
    {
        if (!array_key_exists($key, $this->payload)) {
            throw new RuntimeException('Payload key ' . $key . ' does not exist');
        }

        return $this->payload[$key];
    }

    public function __isset(string $key)
    {
        return isset($this->payload[$key]);
    }

    public function __set(string $key, $value)
    {
        $this->payload[$key] = $value;
    }

    public function toArray(): array
    {
        return $this->payload;
    }
}