<?php

namespace Kirby\Cache\Compression;

/**
 * Brotli compression encoder
 *
 * Requires the `brotli` PHP extension to be loaded.
 *
 * @package   Kirby Staticache
 * @author    Tobias Möritz
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class BrotliEncoder implements CompressionEncoder
{
	public function encoding(): string
	{
		return 'br';
	}

	public function extension(): string
	{
		return 'br';
	}

	public function defaultLevel(): int
	{
		return BROTLI_COMPRESS_LEVEL_DEFAULT;
	}

	public function encode(string $content, int $level): string|false
	{
		return brotli_compress($content, $level);
	}
}
