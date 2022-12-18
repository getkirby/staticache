<?php

namespace Kirby\Cache;

use Kirby\Filesystem\F;

/**
 * An alternative implementation for the pages cache
 * that caches full HTML files to be read directly
 * by the web server.
 *
 * @package   Kirby Staticache
 * @author    Bastian Allgeier <bastian@getkirby.com>,
 *            Lukas Bestle <lukas@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class StatiCache extends FileCache
{
	/**
	 * Sets all parameters which are needed for the file cache
	 */
	public function __construct(array $options)
	{
		parent::__construct($options);
		$this->root = kirby()->root('index') . '/static';
	}

	/**
	 * Internal method to retrieve the raw cache value;
	 * needs to return a Value object or null if not found
	 */
	public function retrieve(string $key): Value|null
	{
		$file  = $this->file($key);
		$value = F::read($file);

		if (is_string($value) === true) {
			return new Value($value, 0, filemtime($file));
		}

		return null;
	}

	/**
	 * Writes an item to the cache for a given number of minutes and
	 * returns whether the operation was successful
	 */
	public function set(string $key, $value, int $minutes = 0): bool
	{
		return F::write($this->file($key), $value['html'] . '<!-- static -->');
	}

	/**
	 * Returns the full path to a file for a given key
	 */
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
}
