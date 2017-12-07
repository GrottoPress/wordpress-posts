# WordPress Posts

## Description

A library to query and display posts in WordPress.

## Usage

Install via composer:

`composer require grottopress/wordpress-posts`

You may use the styles defined in `dist/styles` in your theme (or plugin):

    add_action('wp_enqueue_scripts', function () {
        wp_enqueue_style(
            'wordpress-posts',
            get_template_directory_uri().'/vendor/grottopress/wordpress-posts/dist/styles/posts.min.css'
        );
    });

Use thus:

    use GrottoPress\WordPress\Posts\Posts;

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
                'types' => ['share_link', 'tweet_link', 'googleplus_link'],
            ],
            'after' => [
                'types' => ['author_name', 'published_date', 'comments_link'],
            ]
        ],
        'wp_query' => [ // WP_Query args
            // See https://codex.wordpress.org/Class_Reference/WP_Query
        ]
    ]);

    // display posts
    echo $posts->render();

Full list of arguments, with their defaults, are as follows:

    $default_args = [
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
            'link' => 1,
        ],
        'excerpt' => [
            'length' => 0, // Number of words. Use -1 for full excerpt, -2 for full content
            'paginate' => 1, // If showing full content, whether or not to paginate.
            'more_text' => \esc_html__('read more'),
            'after' => [
                'types' => [], // Info to display after content/excerpt
                'separator' => '|',
                'before' => '<aside class="entry-meta after-content">',
                'after' => '</aside>',
            ],
        ],
        'title' => [
            'tag' => 'h2',
            'position' => '', // Relative to image: 'top' or 'side'
            'length' => -1, // Number of words. Use -1 for full length
            'link' => 1,
            'before' => [
                'types' => [], // Info to display before title
                'separator' => '|',
                'before' => '<aside class="entry-meta before-title">',
                'after' => '</aside>',
            ],
            'after' => [
                'types' => [], // Info to display after title
                'separator' => '|',
                'before' => '<aside class="entry-meta after-title">',
                'after' => '</aside>',
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
