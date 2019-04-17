# WordPress Posts

Query and display posts in WordPress.

## Usage

Install via composer:

```bash
composer require grottopress/wordpress-posts
```

You may use the styles defined in `dist/styles` in your theme (or plugin):

```php
\add_action('wp_enqueue_scripts', function () {
    \wp_enqueue_style(
        'wordpress-posts',
        \get_template_directory_uri().'/vendor/grottopress/wordpress-posts/dist/styles/posts.min.css'
    );
});
```

Use thus:

```php
<?php
declare (strict_types = 1);

use GrottoPress\WordPress\Posts;

// Instantiate Posts
$posts = new Posts([
    'id' => 'my-awesome-posts',
    'image' => [
        'size' => 'some-size', //could be array (eg: array(150,150)) or string (eg: 'post-thubnail')
        'margin' => '0 10px 0 0',
    ],
    'excerpt' => [
        'length' => 30, // number of words. use -1 for all
        'after' => [
            'types' => ['category', 'post_tag']
        ]
    ],
    'title' => [
        'position' => 'top' // either 'top' or 'side' of image
        'tag' => 'h1', // 'h2' by default,
        'before' => [
            'types' => ['share_link', 'tweet_link'],
        ],
        'after' => [
            'types' => ['author_name', 'published_date', 'comments_link'],
        ]
    ],
    'wp_query' => [ // WP_Query args
        // See https://codex.wordpress.org/Class_Reference/WP_Query
    ]
]);

// Display posts
echo $posts->render();
```

## Arguments

Full list of arguments, with their defaults, are as follows:

```php
$args = [
    'id' => '', // Unique ID
    'class' => 'small', // Wrapper HTML classes
    'tag' => 'div', // Wrapper HTML tag. Use 'ul' for list posts.
    'layout' => 'stack', // 'stack' or 'grid'
    'text_offset' => 0, // Distance from image side to text (title, excerpt)
    'related_to' => 0, // Post ID. Use this for related posts
    'image' => [
        'size' => '',
        'align' => '', // 'left', 'right' or 'none'
        'margin' => '',
        'link' => true,
    ],
    'excerpt' => [
        'length' => 0, // Number of words. Use -1 for full excerpt, -2 for full content
        'paginate' => 1, // If showing full content, whether or not to paginate.
        'more_text' => \esc_html__('read more'),
        'after' => [
            'types' => [], // Info to display after content/excerpt
            'separator' => '|',
            'before' => '<small class="entry-meta after-content">',
            'after' => '</small>',
        ],
    ],
    'title' => [
        'tag' => 'h2',
        'position' => '', // Relative to image: 'top' or 'side'
        'length' => -1, // Number of words. Use -1 for full length
        'link' => true,
        'before' => [
            'types' => [], // Info to display before title
            'separator' => '|',
            'before' => '<small class="entry-meta before-title">',
            'after' => '</small>',
        ],
        'after' => [
            'types' => [], // Info to display after title
            'separator' => '|',
            'before' => '<small class="entry-meta after-title">',
            'after' => '</small>',
        ],
    ],
    'pagination' => [
        'position' => [], // 'top' and/or 'bottom'
        'key' => '', // URL query key to use for pagination. Defaults to 'pag'.
        'mid_size' => null,
        'end_size' => null,
        'prev_text' => \esc_html__('&larr; Previous'),
        'next_text' => \esc_html__('Next &rarr;'),
    ],
    'wp_query' => [ // WP_Query args
        // See https://codex.wordpress.org/Class_Reference/WP_Query
    ]
]
```

## Posts info

The following are possible values you may supply to `$args['title']['before']['types']`, `$args['title']['after']['types']` and `$args['excerpt']['after']['types']`:

- `avatar__<size>` eg: `avatar__40`
- `updated_ago`, `updated_ago__actual`, `updated_ago__difference`
- `published_ago`, `published_ago__actual`, `published_ago__difference`
- `author_name`
- `comments_link`
- `updated_date`
- `updated_time`
- `published_date`
- `published_time`
- `category_list` or `category`
- `tag_list` or `post_tag`
- `edit_link`
- `delete_link`
- `tweet_button`
- `sharethis_button`
- `share_link`
- `tweet_link`
- `pin_link`
- `linkedin_link`
- `buffer_link`
- `digg_link`
- `tumblr_link`
- `reddit_link`
- `blogger_link`
- `pocket_link`
- `skype_link`
- `viber_link`
- `whatsapp_link`
- `telegram_link`
- `vk_link`
- The name of a filter hook. A function should then be defined and added to that filter. Function args: `string $output, int $post_id, string $separator`.
- A post meta key. This would display a single meta value for that key.
- A taxonomy name. This would display a list of all terms of that taxonomy the post was assigned to.

## Social media icons

If you would like to show icons for social links, you need to install [font awesome v5](https://fontawesome.com/).
