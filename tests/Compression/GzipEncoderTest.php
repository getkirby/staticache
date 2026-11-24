<?php

namespace Kirby\Cache\Compression;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GzipEncoder::class)]
class GzipEncoderTest extends TestCase
{
	public function testDefaultLevel(): void
	{
		$this->assertSame(6, (new GzipEncoder())->defaultLevel());
	}

	public function testEncode(): void
	{
		$encoder = new GzipEncoder();
		$content = str_repeat('<p>Static site performance on demand</p>', 100);
		$encoded = $encoder->encode($content, $encoder->defaultLevel());

		$this->assertIsString($encoded);
		$this->assertLessThan(strlen($content), strlen($encoded));
		$this->assertSame($content, gzdecode($encoded));
	}

	public function testEncoding(): void
	{
		$this->assertSame('gzip', (new GzipEncoder())->encoding());
	}

	public function testExtension(): void
	{
		$this->assertSame('gz', (new GzipEncoder())->extension());
	}
}
