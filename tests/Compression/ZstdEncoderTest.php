<?php

namespace Kirby\Cache\Compression;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(ZstdEncoder::class)]
#[RequiresPhpExtension('zstd')]
class ZstdEncoderTest extends TestCase
{
	public function testDefaultLevel(): void
	{
		$this->assertSame(ZSTD_COMPRESS_LEVEL_DEFAULT, (new ZstdEncoder())->defaultLevel());
	}

	public function testEncode(): void
	{
		$encoder = new ZstdEncoder();
		$content = str_repeat('<p>Static site performance on demand</p>', 100);
		$encoded = $encoder->encode($content, $encoder->defaultLevel());

		$this->assertIsString($encoded);
		$this->assertLessThan(strlen($content), strlen($encoded));
		$this->assertSame($content, zstd_uncompress($encoded));
	}

	public function testEncoding(): void
	{
		$this->assertSame('zstd', (new ZstdEncoder())->encoding());
	}

	public function testExtension(): void
	{
		$this->assertSame('zst', (new ZstdEncoder())->extension());
	}
}
