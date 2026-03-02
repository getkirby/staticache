<?php

namespace Kirby\Cache\Compression;

/**
 * Gzip compression encoder using PHP's built-in zlib
 *
 * @package   Kirby Staticache
 * @author    Tobias Möritz
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class GzipEncoder implements CompressionEncoder
{
	public function encoding(): string
	{
		return 'gzip';
	}

	public function extension(): string
	{
		return 'gz';
	}

	public function defaultLevel(): int
	{
		return 6;
	}

	public function encode(string $content, int $level): string|false
	{
		return gzencode($content, $level);
	}
}
