<?php

namespace Kirby\Cache;

use Kirby\Cms\App;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(StatiCache::class)]
class StatiCacheTest extends TestCase
{
	protected string $tmp;

	protected function setUp(): void
	{
		$this->tmp = sys_get_temp_dir() . '/staticache-' . bin2hex(random_bytes(8));

		F::write($this->tmp . '/site/templates/default.php', '<h1><?= $page->title() ?></h1>');
		F::write($this->tmp . '/site/templates/default.json.php', '<?= json_encode([\'title\' => $page->title()->value()]) ?>');
		F::write($this->tmp . '/site/templates/custom.php', '<?php $kirby->response()->code(202)->header(\'X-Staticache\', \'yes\') ?>Custom');
	}

	protected function tearDown(): void
	{
		Dir::remove($this->tmp);
	}

	/**
	 * Creates a Kirby instance with the static pages cache
	 * and its content, in each given language
	 *
	 * @param array<string>|null $languages Language codes, the first is the default
	 */
	protected function app(array $options = [], array|null $languages = null): App
	{
		foreach ($languages ?? [null] as $code) {
			$suffix = $code !== null ? '.' . $code : '';
			$label  = $code !== null ? ' ' . strtoupper($code) : '';

			F::write($this->tmp . '/content/home/home' . $suffix . '.txt', 'Title: Home' . $label);
			F::write($this->tmp . '/content/error/error' . $suffix . '.txt', 'Title: Error' . $label);
			F::write($this->tmp . '/content/1_notes/default' . $suffix . '.txt', 'Title: Notes' . $label);
			F::write($this->tmp . '/content/1_notes/1_first/default' . $suffix . '.txt', 'Title: First note' . $label);
			F::write($this->tmp . '/content/2_custom/custom' . $suffix . '.txt', 'Title: Custom' . $label);
		}

		return new App([
			'roots' => [
				'index' => $this->tmp
			],
			'urls' => [
				'index' => 'https://example.com'
			],
			'languages' => $languages !== null ? array_map(
				fn (string $code) => ['code' => $code, 'default' => $code === $languages[0]],
				$languages
			) : null,
			'options' => [
				'cache' => [
					'pages' => [
						'active' => true,
						'type'   => 'static',
						...$options
					]
				]
			]
		]);
	}

	public function testCacheType(): void
	{
		$this->assertInstanceOf(StatiCache::class, $this->app()->cache('pages'));
	}

	public function testFlushOnPageUpdate(): void
	{
		$app  = $this->app(['compression' => ['gzip']]);
		$root = $app->cache('pages')->root();

		$app->render('notes');
		$this->assertFileExists($root . '/notes/index.html');

		$app->impersonate('kirby', fn () => $app->page('notes/first')->update(['title' => 'Updated']));

		$this->assertFileDoesNotExist($root . '/notes/index.html');
		$this->assertFileDoesNotExist($root . '/notes/index.html.gz');
	}

	public function testMultilang(): void
	{
		$app   = $this->app(languages: ['en', 'de']);
		$cache = $app->cache('pages');
		$root  = $cache->root();

		$app->render('en');
		$app->render('en/notes');
		$app->render('de/notes');

		$this->assertStringStartsWith('<h1>Home EN</h1>', F::read($root . '/en/index.html'));
		$this->assertStringStartsWith('<h1>Notes EN</h1>', F::read($root . '/en/notes/index.html'));
		$this->assertStringStartsWith('<h1>Notes DE</h1>', F::read($root . '/de/notes/index.html'));

		// representations, including the one of the home page
		$app->page('notes')->render([], 'json');
		$app->page('home')->render([], 'json');

		$this->assertSame('{"title":"Notes DE"}', F::read($root . '/de/notes.json'));
		$this->assertSame('{"title":"Home DE"}', F::read($root . '/de/home.json'));

		// only the given language is removed
		$this->assertTrue($cache->remove('notes.de.latest.html'));
		$this->assertFileDoesNotExist($root . '/de/notes/index.html');
		$this->assertFileExists($root . '/en/notes/index.html');
	}

	public function testRemove(): void
	{
		$app   = $this->app(['compression' => ['gzip']]);
		$cache = $app->cache('pages');
		$root  = $cache->root();

		$app->render('');
		$app->render('notes');

		$this->assertTrue($cache->remove('notes.latest.html'));
		$this->assertFileDoesNotExist($root . '/notes/index.html');
		$this->assertFileDoesNotExist($root . '/notes/index.html.gz');

		// other pages stay cached
		$this->assertFileExists($root . '/index.html');
		$this->assertFileExists($root . '/index.html.gz');

		$this->assertFalse($cache->remove('notes.latest.html'));
	}

	public function testRetrieve(): void
	{
		$app   = $this->app();
		$cache = $app->cache('pages');
		$file  = $cache->root() . '/notes/index.html';

		$this->assertNull($cache->retrieve('notes.latest.html'));

		$app->render('notes');
		$value = $cache->retrieve('notes.latest.html');

		$this->assertInstanceOf(Value::class, $value);
		$this->assertSame(F::read($file), $value->value());
		$this->assertSame(filemtime($file), $value->created());
	}

	public static function commentProvider(): array
	{
		return [
			'string' => [
				'<!-- cached -->',
				'<h1>Notes</h1><!-- cached -->',
				'{"title":"Notes"}'
			],
			'empty string' => [
				'',
				'<h1>Notes</h1>',
				'{"title":"Notes"}'
			],
			'closure' => [
				fn (string $contentType) => '/* ' . $contentType . ' */',
				'<h1>Notes</h1>/* html */',
				'{"title":"Notes"}/* json */'
			]
		];
	}

	#[DataProvider('commentProvider')]
	public function testSetComment(mixed $comment, string $html, string $json): void
	{
		$app  = $this->app(['comment' => $comment]);
		$root = $app->cache('pages')->root();

		$app->render('notes');
		$app->page('notes')->render([], 'json');

		$this->assertSame($html, F::read($root . '/notes/index.html'));
		$this->assertSame($json, F::read($root . '/notes.json'));
	}

	public static function compressionProvider(): array
	{
		// the XFL byte of the gzip header reflects the compression level
		return [
			'default level' => [['gzip'], 0],
			'maximum level' => [['gzip' => 9], 2],
			'fastest level' => [['gzip' => 1], 4]
		];
	}

	#[DataProvider('compressionProvider')]
	public function testSetCompressed(array $compression, int $xfl): void
	{
		$app  = $this->app(['compression' => $compression]);
		$file = $app->cache('pages')->root() . '/notes/index.html';

		$app->render('notes');
		$gzip = F::read($file . '.gz');

		$this->assertSame(F::read($file), gzdecode($gzip));
		$this->assertSame($xfl, ord($gzip[8]));
	}

	public static function invalidCompressionProvider(): array
	{
		return [
			'unknown encoding' => [['deflate']],
			'no array'         => ['gzip']
		];
	}

	#[DataProvider('invalidCompressionProvider')]
	public function testSetCompressedInvalid(mixed $compression): void
	{
		$app  = $this->app(['compression' => $compression]);
		$root = $app->cache('pages')->root();

		$app->render('notes');

		$this->assertSame(['index.html'], Dir::files($root . '/notes'));
	}

	public static function htmlProvider(): array
	{
		return [
			'home page'  => ['', 'index.html', 'Home'],
			'page'       => ['notes', 'notes/index.html', 'Notes'],
			'child page' => ['notes/first', 'notes/first/index.html', 'First note']
		];
	}

	#[DataProvider('htmlProvider')]
	public function testSetHtml(string $path, string $file, string $title): void
	{
		$app  = $this->app();
		$root = $app->cache('pages')->root();

		$app->render($path);

		$this->assertMatchesRegularExpression(
			'/^<h1>' . $title . '<\/h1><!-- static \d{4}-\d{2}-\d{2}T\S+ -->$/',
			F::read($root . '/' . $file)
		);
	}

	public static function representationProvider(): array
	{
		return [
			'home page'  => ['home', 'home.json', 'Home'],
			'page'       => ['notes', 'notes.json', 'Notes'],
			'child page' => ['notes/first', 'notes/first.json', 'First note']
		];
	}

	#[DataProvider('representationProvider')]
	public function testSetRepresentation(string $id, string $file, string $title): void
	{
		$app  = $this->app();
		$root = $app->cache('pages')->root();

		$app->page($id)->render([], 'json');

		$this->assertSame(json_encode(['title' => $title]), F::read($root . '/' . $file));
	}

	public static function headersProvider(): array
	{
		return [
			'page' => [
				'notes',
				'html',
				'notes/index.html',
				"Status: 200\nContent-Type: text/html\n\n<h1>Notes</h1><!-- static "
			],
			'custom response' => [
				'custom',
				'html',
				'custom/index.html',
				"Status: 202\nContent-Type: text/html\nX-Staticache: yes\n\nCustom<!-- static "
			],
			'representation' => [
				'notes',
				'json',
				'notes.json',
				"Status: 200\nContent-Type: application/json\n\n{\"title\":\"Notes\"}"
			]
		];
	}

	#[DataProvider('headersProvider')]
	public function testSetWithHeaders(string $id, string $contentType, string $file, string $expected): void
	{
		$app  = $this->app(['headers' => true]);
		$root = $app->cache('pages')->root();

		$app->page($id)->render([], $contentType);

		$this->assertStringStartsWith($expected, F::read($root . '/' . $file));
	}
}
