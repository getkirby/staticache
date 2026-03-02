<?php

namespace Kirby\Cache;

use Closure;
use Kirby\Cms\App;
use Kirby\Cms\Url;
use Kirby\Filesystem\F;
use Kirby\Filesystem\Mime;
use Kirby\Cache\Compression\BrotliEncoder;
use Kirby\Cache\Compression\CompressionEncoder;
use Kirby\Cache\Compression\GzipEncoder;
use Kirby\Cache\Compression\ZstdEncoder;
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

		$file    = $this->file($cacheId);
		$success = F::write($file, $result);

		if ($success === true) {
			$this->writeCompressed($file, $result);
		}

		return $success;
	}

	/**
	 * Removes an item from the cache and
	 * returns whether the operation was successful
	 *
	 * Overrides the parent to also remove compressed
	 * copies that the parent doesn't know about.
	 */
	public function remove(string $key): bool
	{
		$file = $this->file(static::parseCacheId($key));

		foreach ($this->compressionConfig() as ['encoder' => $encoder]) {
			F::remove($file . '.' . $encoder->extension());
		}

		return parent::remove($key);
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

		// Check for the new Version API in Kirby 5
		// Split a cache ID into `$id.$language.$version.$contentType`
		if (class_exists('Kirby\Content\Version') === true) {
			$version = array_pop($parts);
		} else {
			$version = null;
		}

		$language = $kirby->multilang() === true ? array_pop($parts) : null;
		$id       = implode('.', $parts);

		return compact('id', 'language', 'contentType', 'version');
	}

	/**
	 * Writes compressed copies of a cached file for each
	 * configured compression encoding
	 */
	protected function writeCompressed(string $file, string $content): void
	{
		foreach ($this->compressionConfig() as ['encoder' => $encoder, 'level' => $level]) {
			$compressed = $encoder->encode($content, $level);

			if ($compressed !== false) {
				F::write($file . '.' . $encoder->extension(), $compressed);
			}
		}
	}

	/**
	 * Returns the available compression encoders keyed by encoding name
	 *
	 * Only returns encoders whose PHP extensions are loaded.
	 *
	 * @return array<string, CompressionEncoder>
	 */
	protected static function encoders(): array
	{
		$encoders = [
			'gzip' => new GzipEncoder(),
		];

		if (extension_loaded('brotli') === true) {
			$encoders['br'] = new BrotliEncoder();
		}

		if (extension_loaded('zstd') === true) {
			$encoders['zstd'] = new ZstdEncoder();
		}

		return $encoders;
	}

	/**
	 * Parses the `compression` option into a normalized array
	 * of encoder/level pairs
	 *
	 * Supports both `['gzip']` (default level) and
	 * `['gzip' => 9]` (explicit level) syntax.
	 *
	 * @return array<int, array{encoder: CompressionEncoder, level: int}>
	 */
	protected function compressionConfig(): array
	{
		$config = $this->options['compression'] ?? [];

		if (is_array($config) === false) {
			return [];
		}

		$encoders = static::encoders();
		$result   = [];

		foreach ($config as $key => $value) {
			if (is_int($key) === true) {
				$encoding = $value;
				$encoder  = $encoders[$encoding] ?? null;
				$level    = $encoder?->defaultLevel();
			} else {
				$encoding = $key;
				$encoder  = $encoders[$encoding] ?? null;
				$level    = $value;
			}

			if ($encoder !== null) {
				$result[] = ['encoder' => $encoder, 'level' => $level];
			}
		}

		return $result;
	}
}
