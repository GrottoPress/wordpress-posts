<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress;

use GrottoPress\Mobile\Detector;
use WP_Post;
use WP_Post_Type;

class Post
{
    /**
     * @var int
     */
    protected $id;

    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    /**
     * @param string $context 'published' or 'updated'
     */
    public function time(string $context = ''): Post\Time
    {
        return new Post\Time($this, $context);
    }

    public function info(array $args): Post\Info
    {
        return new Post\Info($this, new Detector(), $args);
    }

    public function author(): Post\Author
    {
        return new Post\Author($this);
    }

    public function comments(): Post\Comments
    {
        return new Post\Comments($this);
    }

    public function get(): WP_Post
    {
        return \get_post($this->id);
    }

    public function type(): WP_Post_Type
    {
        return \get_post_type_object($this->get()->post_type);
    }

    public function typeSupports(string $feature): bool
    {
        return \post_type_supports($this->get()->post_type, $feature);
    }

    public function hasThumbnail(): bool
    {
        return \has_post_thumbnail($this->get());
    }

    public function meta(string $key, bool $single = false)
    {
        return \get_post_meta($this->get()->ID, \sanitize_key($key), $single);
    }

    /**
     * @param int $num Number of characters/words.
     */
    public function excerpt(int $num = -1, string $more_text = ''): string
    {
        if (0 === $num) {
            return '';
        }

        if ($more_text) {
            $more_text = '<span class="ellipsis">...</span> <a class="more-link" itemprop="url" href="'.
            \get_permalink($this->get()).'">'
               .\sanitize_text_field($more_text)
            .'</a>';
        } else {
            $more_text = \apply_filters('excerpt_more', ' [&hellip;]');
        }

        $excerpt = $this->get()->post_excerpt
            ? $this->get()->post_excerpt : $this->get()->post_content;

        $excerpt = \strip_shortcodes($excerpt);

        if ($num > 0) {
            return \wp_trim_words($excerpt, $num, $more_text);
        }

        return \wp_trim_words(
            $excerpt,
            \apply_filters('excerpt_length', 55),
            $more_text
        );
    }

    /**
     * @param boolean $paging Show navigation links for multipage content?
     */
    public function content(
        string $more_text = '',
        string $stripteaser = '',
        bool $paging = false
    ): string {
        global $post, $more;

        $post = $this->get();
        \setup_postdata($post);

        $more = $more_text ? 0 : 1;

        $content = \apply_filters(
            'the_content',
            \get_the_content($more_text, $stripteaser)
        );

        if ($paging && $content && $more) {
            $content .= \wp_link_pages([
                'before' => '<nav class="page-links pagination">'
                   .\esc_html__('Pages: '),
                'after' => '</nav>',
                'echo' => 0,
            ]);
        }

        \wp_reset_postdata();

        return $content;
    }

    /**
     * @param array $attr Image atrtributes.
     * @param bool $link Link image to post URL?
     */
    public function thumbnail(
        $size = '',
        array $attr = [],
        bool $link = true
    ): string {
        $out = '';
        $class = '';

        if (\is_string($size)) {
            if (0 === \stripos($size, 'avatar__')) {
                $size = \absint(\str_ireplace('avatar__', '', $size));

                return \get_avatar($this->get()->post_author, $size);
            }

            $size_split = \explode(',', $size);

            // If $size contain 2 comma-separated integers
            if (0 !== \absint($size_split[0])) {
                $size = $size_split;
            } else {
                $class .= \sanitize_title($size);
            }
        }

        $attr['class'] = $attr['class'] ?? '';
        $attr['itemprop'] = $attr['itemprop'] ?? '';

        $attr['class'] .= " thumb {$class}";
        $attr['class'] = \trim($attr['class']);

        $attr['itemprop'] .= ' image';
        $attr['itemprop'] = \trim($attr['itemprop']);

        if ($link) {
            $out .= '<a class="image-link post-thumb-link" href="'.
                \get_permalink($this->get()).'" rel="bookmark" itemprop="url">';
        }

        $out .= \get_the_post_thumbnail($this->get(), $size, $attr);

        if ($link) {
            $out .= '</a>';
        }

        return $out;
    }

    /**
     * @param string $context Hierarchical or not.
     */
    public function taxonomies(string $context = ''): array
    {
        $tax = [];

        if (!($objects = \get_object_taxonomies(
            $this->get()->post_type,
            'objects'
        ))) {
            return $tax;
        }

        foreach ($objects as $slug => $object) {
            if ('hierarchical' === $context
                && !\is_taxonomy_hierarchical($slug)
            ) {
                continue;
            }

            if ('non_hierarchical' === $context
                && \is_taxonomy_hierarchical($slug)
            ) {
                continue;
            }

            $terms = \get_the_terms($this->get(), $slug);

            if (!$terms || \is_wp_error($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                $tax[$slug][(int)$term->term_id] = $term;
            }
        }

        return $tax;
    }
}
