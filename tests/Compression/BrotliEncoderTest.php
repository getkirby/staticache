<?php

namespace Kirby\Cache\Compression;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(BrotliEncoder::class)]
#[RequiresPhpExtension('brotli')]
class BrotliEncoderTest extends TestCase
{
	public function testDefaultLevel(): void
	{
		$this->assertSame(BROTLI_COMPRESS_LEVEL_DEFAULT, (new BrotliEncoder())->defaultLevel());
	}

	public function testEncode(): void
	{
		$encoder = new BrotliEncoder();
		$content = str_repeat('<p>Static site performance on demand</p>', 100);
		$encoded = $encoder->encode($content, $encoder->defaultLevel());

		$this->assertIsString($encoded);
		$this->assertLessThan(strlen($content), strlen($encoded));
		$this->assertSame($content, brotli_uncompress($encoded));
	}

	public function testEncoding(): void
	{
		$this->assertSame('br', (new BrotliEncoder())->encoding());
	}

	public function testExtension(): void
	{
		$this->assertSame('br', (new BrotliEncoder())->extension());
	}
}
