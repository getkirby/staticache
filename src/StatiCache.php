<?php

namespace Kirby\Cache;

use Kirby\Filesystem\F;

class StatiCache extends FileCache
{

    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->root = kirby()->root('index') . '/static';
    }

    public function file(string $key): string
    {
        $path      = dirname($key);
        $name      = F::name($key);
        $extension = F::extension($key);

        if ($name === 'home') {
            return $this->root . '/index.html';
        }

        return $this->root . '/' . $path . '/' . $name . '/index.' . $extension;
    }

    public function retrieve(string $key): Value|null
    {
        $value = F::read($this->file($key));
        return $value ? new Value($value) : null;
    }

    public function set(string $key, $value, int $minutes = 0): bool
    {
        return F::write($this->file($key), $value['html'] . '<!-- static -->');
    }

}
