# Kirby Staticache Plugin

Static site performance on demand!

This plugin will give you the performance of a static site generator for your regular Kirby installations. Without a huge setup or complex deploy steps, you can just run your Kirby site on any server – cheap shared hosting, VPS, you name it – and switch on the static cache to get incredible speed on demand. 

With custom ignore rules, you can even mix static and dynamic content. Keep some pages static while others are still served live by Kirby. 

The static cache will automatically be flushed whenever content gets updated in the Panel. It's truly the best of both worlds. 

Rough benchmark comparison for our starterkit home page: 

Without page cache: ~70 ms  
With page cache: ~30 ms   
With static cache: ~10 ms

## 🚨 Experimental

This plugin is still an experiment. The first results are very promising but it needs to be tested on more servers and has a couple open todos:

- [x] Nginx config example
- [ ] Caddy config example
- [x] Publish on Packagist to be installable via composer
- [x] Hooks to automatically flush the cache when content is updated via the Panel
- [x] Add options to ignore pages from caching

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

Staticache is just a cache driver that can be activated for the page cache:

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active' => true,
      'type' => 'static'
    ]
  ]
];
```

You can also use the cache ignore rules to skip pages that should not be cached:
https://getkirby.com/docs/guide/cache#caching-pages

```php
// /site/config/config.php

return [
  'cache' => [
    'pages' => [
      'active' => true,
      'type' => 'static',
      'ignore' => function ($page) {
        return $page->template()->name() === 'blog';
      }
    ]
  ]
];
```

Kirby will automatically purge the cache when changes are made in the Panel.

### htaccess rules

Add the following lines to your Kirby htaccess file, directly after the RewriteBase rule.

```
RewriteCond %{DOCUMENT_ROOT}/static/%{REQUEST_URI}/index.html -f [NC]
RewriteRule ^(.*) %{DOCUMENT_ROOT}/static/%{REQUEST_URI}/index.html [L]
```

### nginx

Standard php nginx config will have this location block for all requests

```
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
```
change it to add `/static/$uri/index.html` before last `/index.php` fallback

```
    location / {
        try_files $uri $uri/ /static/$uri/index.html /index.php?$query_string;
    }
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
