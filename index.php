<?php

load([
	'Kirby\Cache\StatiCache' => __DIR__ . '/src/StatiCache.php'
]);

Kirby::plugin('getkirby/staticache', [
	'cacheTypes' => [
		'static' => 'Kirby\Cache\StatiCache'
	]
]);
