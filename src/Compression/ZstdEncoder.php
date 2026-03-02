<?php

namespace Kirby\Cache\Compression;

/**
 * Zstandard compression encoder
 *
 * Requires the `zstd` PHP extension to be loaded.
 *
 * @package   Kirby Staticache
 * @author    Tobias Möritz
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class ZstdEncoder implements CompressionEncoder
{
	public function encoding(): string
	{
		return 'zstd';
	}

	public function extension(): string
	{
		return 'zst';
	}

	public function defaultLevel(): int
	{
		return ZSTD_COMPRESS_LEVEL_DEFAULT;
	}

	public function encode(string $content, int $level): string|false
	{
		return zstd_compress($content, $level);
	}
}
