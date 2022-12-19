<?php

namespace Kirby\Cache;

use Kirby\Cms\App;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

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
		$cacheId = static::parseCacheId($key);

		$body = $value['html'];

		if ($cacheId['contentType'] === 'html') {
			$body .= '<!-- static ' . date('c') . ' -->';
		}

		return F::write($this->file($cacheId), $body);
	}

	/**
	 * Returns the full path to a file for a given key
	 */
	protected function file(string|array $key): string
	{
		$kirby = App::instance();

		// compatibility with other cache drivers
		if (is_string($key) === true) {
			$key = static::parseCacheId($key);
		}

		$page = $kirby->page($key['id']);
		$url  = $page->url($key['language']);

		// content representation paths of the home page contain the home slug
		if ($page->isHomePage() === true && $key['contentType'] !== 'html') {
			$url .= '/' . $page->uri($key['language']);
		}

		// we only care about the path
		$root = $this->root . '/' . ltrim(Str::after($url, $kirby->url('index')), '/');

		if ($key['contentType'] === 'html') {
			return rtrim($root, '/') . '/index.html';
		}

		return $root . '.' . $key['contentType'];
	}

	/**
	 * Splits a cache ID into `$id.$language.$contentType`
	 */
	protected static function parseCacheId(string $key): array
	{
		$kirby = App::instance();

		$parts       = explode('.', $key);
		$contentType = array_pop($parts);
		$language    = $kirby->multilang() === true ? array_pop($parts) : null;
		$id          = implode('.', $parts);

		return compact('id', 'language', 'contentType');
	}
}
