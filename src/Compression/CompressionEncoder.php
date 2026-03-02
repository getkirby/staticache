<?php

namespace Kirby\Cache\Compression;

/**
 * Interface for compression encoders used by StatiCache
 * to write pre-compressed copies of cached files
 *
 * @package   Kirby Staticache
 * @author    Tobias Möritz
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
interface CompressionEncoder
{
	/**
	 * Returns the encoding name
	 * (e.g. 'gzip', 'br', 'zstd')
	 */
	public function encoding(): string;

	/**
	 * Returns the file extension for compressed files
	 * (e.g. 'gz', 'br', 'zst')
	 */
	public function extension(): string;

	/**
	 * Returns the default compression level
	 */
	public function defaultLevel(): int;

	/**
	 * Encodes the given content at the specified compression level
	 *
	 * @return string|false The compressed content or false on failure
	 */
	public function encode(string $content, int $level): string|false;
}
