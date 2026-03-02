<?php

load([
	'Kirby\Cache\StatiCache' => __DIR__ . '/src/StatiCache.php',
	'Kirby\Cache\Compression\BrotliEncoder' => __DIR__ . '/src/Compression/BrotliEncoder.php',
	'Kirby\Cache\Compression\CompressionEncoder' => __DIR__ . '/src/Compression/CompressionEncoder.php',
	'Kirby\Cache\Compression\GzipEncoder' => __DIR__ . '/src/Compression/GzipEncoder.php',
	'Kirby\Cache\Compression\ZstdEncoder' => __DIR__ . '/src/Compression/ZstdEncoder.php'
]);

Kirby::plugin('getkirby/staticache', [
	'cacheTypes' => [
		'static' => 'Kirby\Cache\StatiCache'
	]
]);
