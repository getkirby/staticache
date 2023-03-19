<?php

namespace Kirby\Cache;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Url;
use Kirby\Filesystem\F;
use Kirby\Filesystem\Mime;
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

		// body
		$result = $this->appendComment($value['html'], $cacheId['contentType']);

		// headers (if enabled)
		if (
			isset($this->options['headers']) === true &&
			$this->options['headers'] === true
		) {
			$headers = static::headersFromResponse($value['response'], $cacheId['contentType']);
			$result  = $headers . "\n\n" . $result;
		}

		return F::write($this->file($cacheId), $result);
	}

	/**
	 * Appends a (HTML) comment to a cached body for
	 * identification of cached responses
	 */
	protected function appendComment(string $body, string $contentType): string
	{
		// custom string or callback
		if (isset($this->options['comment']) === true) {
			$comment = $this->options['comment'];

			if ($comment instanceof Closure) {
				return $body . $comment($contentType);
			}

			// use string comments for HTML bodies only
			if (is_string($comment) === true && $contentType === 'html') {
				return $body . $comment;
			}

			return $body;
		}

		// default implementation
		if ($contentType === 'html') {
			$body .= '<!-- static ' . date('c') . ' -->';
		}

		return $body;
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
		$url  = $page?->url($key['language']) ?? Url::to($key['id']);

		// content representation paths of the home page contain the home slug
		if ($page?->isHomePage() === true && $key['contentType'] !== 'html') {
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
	 * Serializes all headers from a response array to a string of HTTP headers
	 */
	protected static function headersFromResponse(array $response, string $extension): string
	{
		$headers = [
			'Status: ' . ($response['code'] ?? 200),
			'Content-Type: ' . ($response['type'] ?? Mime::fromExtension($extension))
		];

		foreach ($response['headers'] as $key => $value) {
			$headers[] = $key . ': ' . $value;
		}

		return implode("\n", $headers);
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
