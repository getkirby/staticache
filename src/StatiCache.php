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

    protected function file(string $key): string
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
        $file  = $this->file($key);
        $value = F::read($file);

        return $value ? new Value($value) : null;
    }

    public function set(string $key, $value, int $minutes = 0): bool
    {
        $file  = $this->file($key);
        $value = '<!-- static -->' . PHP_EOL . $value['html'];

        return F::write($file, $value);
    }
}
