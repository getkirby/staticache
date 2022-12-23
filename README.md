# Kirby Staticache Plugin

Static site performance on demand!

This plugin will give you the performance of a static site generator for your regular Kirby installations. Without a huge setup or complex deployment steps, you can run your Kirby site on any server – cheap shared hosting, VPS, you name it – and enable the static cache to get incredible speed on demand. 

With custom ignore rules, you can even mix static and dynamic content. Keep some pages static while others are still served live by Kirby.

The static cache will automatically be flushed whenever content gets updated in the Panel. It's truly the best of both worlds. 

Rough benchmark comparison for our Starterkit home page: 

Without page cache: ~70 ms  
With page cache: ~30 ms   
With static cache: ~10 ms

## Limitations

A statically cached page will prevent any Kirby logic from executing. This means that Kirby can no longer differentiate between visitors and logged-in users. Every request will be served directly by your web server, even if the response would differ based on the cookies or other request headers.

If your site has any logic in controllers, page models, templates, snippets or plugins that results in different page responses depending on the request, this logic will naturally not be compatible with Staticache. If only specific pages are affected by this, you can add them to the cache ignore list (see below) and use Staticache for the rest of your site.

## Installation

### Download

Download and copy this repository to `/site/plugins/staticache`.

### Composer

```
composer require getkirby/staticache
```

### Git submodule

```
git submodule add https://github.com/getkirby/staticache.git site/plugins/staticache
```

## Setup

### Cache configuration

**Basic setup:**

Staticache is a cache driver that can be activated for the pages cache:

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active' => true,
      'type'   => 'static'
    ]
  ]
];
```

**Ignore rules:**

If you want to keep some of your pages dynamic, you can configure ignore rules like for the native pages cache: https://getkirby.com/docs/guide/cache#caching-pages

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active' => true,
      'type'   => 'static',
      'ignore' => function ($page) {
        return $page->template()->name() === 'blog';
      }
    ]
  ]
];
```

All pages that are not ignored will automatically be cached on their first visit. Kirby will automatically purge the cache when changes are made in the Panel.

**Custom cache comment:**

Staticache adds an HTML comment like `<!-- static YYYY-MM-DDT01:02:03+00:00 -->` to the end of every cached HTML file by default. You can override or disable this comment in the cache configuration:

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active' => true,
      'type'   => 'static',

      // disabled comment
      'comment' => '',

      // OR string value (only for HTML)
      'comment' => '<!-- your custom comment -->',

      // OR a custom closure
      'comment' => fn ($contentType) => $contentType === 'html' ? '<!-- comment -->' : ''
    ]
  ]
];
```

**Custom root:**

The rendered HTML files are stored in the `site/cache/example.com/pages/` folder just like with the native pages cache. The difference is that all paths within this folder match the URL structure of your site. The separate directories for each root URL ensure that links and references in your rendered HTML keep working even in a multi-domain setup.

If you are using a custom web server setup, you can override the cache root like so:

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active' => true,
      'type'   => 'static',
      'root'   => '/path/to/your/cache/root',
      'prefix' => null
    ]
  ]
];
```

If your site is only served on a single domain, you can disable the root URL prefix like so while keeping the general storage location in the `site/cache` directory:

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active' => true,
      'type'   => 'static',
      'prefix' => 'pages'
    ]
  ]
];
```

If you use a custom root and/or prefix, please modify the following server configuration examples accordingly.

### Web server integration

This plugin will automatically generate and store the cache files, however you need to configure your web server to pick the files up and prefer them over a dynamic result from PHP.

The configuration depends on your used web server:

**Apache:**

Add the following lines to your Kirby `.htaccess` file, directly after the `RewriteBase` rule.

```
RewriteCond %{DOCUMENT_ROOT}/site/cache/%{SERVER_NAME}/pages/%{REQUEST_URI}/index.html -f [NC]
RewriteRule ^(.*) %{DOCUMENT_ROOT}/site/cache/%{SERVER_NAME}/pages/%{REQUEST_URI}/index.html [L]

RewriteCond %{DOCUMENT_ROOT}/site/cache/%{SERVER_NAME}/pages/%{REQUEST_URI} -f [NC]
RewriteRule ^(.*) %{DOCUMENT_ROOT}/site/cache/%{SERVER_NAME}/pages/%{REQUEST_URI} [L]
```

**Caddy:**

A simple Caddy config for Staticache may look like this:

```
example.com

root * /path/to/your/site

file_server
php_fastcgi unix//var/run/php-fpm.sock {
  try_files {path} site/cache/{host}/pages/{path}/index.html site/cache/{host}/pages/{path} index.php
}
```

**nginx:**

Standard PHP nginx config will have this location block for all requests:

```
location / {
  try_files $uri $uri/ /index.php?$query_string;
}
```

Change it to add `/site/cache/$server_addr/pages/$uri/index.html` before the last `/index.php` fallback:

```
location / {
  try_files $uri $uri/ /site/cache/$server_addr/pages/$uri/index.html /site/cache/$server_addr/pages/$uri /index.php?$query_string;
}
```

### Header support

Staticache stores only the response bodies by default. The HTTP status code as well as headers set by your pages are not preserved in this mode. This ensures compatibility with all web servers.

If your web server supports reading headers from the static files, you can enable header support with the `headers` option:

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active'  => true,
      'type'    => 'static',
      'headers' => true
    ]
  ]
];
```

You need to adapt your web server configuration accordingly:

**Apache:**

Header support in Apache requires [`mod_asis`](https://httpd.apache.org/docs/current/mod/mod_asis.html). Please ensure that your Apache installation has this module installed and enabled.

Afterwards add the following block to your `.htaccess` file to make Apache use `mod_asis` for cached files:

```
<Directory "/var/www/your-site/site/cache">
  SetHandler send-as-is
</Directory>
```

## What’s Kirby?
- **[getkirby.com](https://getkirby.com)** – Get to know the CMS.
- **[Try it](https://getkirby.com/try)** – Take a test ride with our online demo. Or download one of our kits to get started.
- **[Documentation](https://getkirby.com/docs/guide)** – Read the official guide, reference and cookbook recipes.
- **[Issues](https://github.com/getkirby/kirby/issues)** – Report bugs and other problems.
- **[Feedback](https://feedback.getkirby.com)** – You have an idea for Kirby? Share it.
- **[Forum](https://forum.getkirby.com)** – Whenever you get stuck, don't hesitate to reach out for questions and support.
- **[Discord](https://chat.getkirby.com)** – Hang out and meet the community.
- **[Mastodon](https://mastodon.social/@getkirby)** – Spread the word.
- **[Instagram](https://www.instagram.com/getkirby/)** – Share your creations: #madewithkirby.

---

## License

[MIT](./LICENSE) License © 2022 [Bastian Allgeier](https://getkirby.com)
