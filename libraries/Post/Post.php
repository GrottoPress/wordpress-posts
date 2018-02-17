<?php

/**
 * WordPress Post
 *
 * @package GrottoPress\WordPress\Post
 * @since 0.1.0
 *
 * @author GrottoPress <info@grottopress.com>
 * @author N Atta Kusi Adusei
 */

declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use WP_Post;
use WP_Post_Type;

/**
 * WordPress Post
 *
 * @since 0.1.0
 */
class Post
{
    /**
     * Post ID
     *
     * @since  0.6.0
     * @access protected
     *
     * @var int
     */
    protected $id;

    /**
     * Constructor
     *
     * @param int $id Post ID.
     *
     * @since 0.1.0
     * @access public
     */
    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    /**
     * Time
     *
     * @param string $context 'published' or 'updated'
     *
     * @since 0.1.0
     * @access public
     *
     * @return Time
     */
    public function time(string $context = ''): Time
    {
        return new Time($this, $context);
    }

    /**
     * Info
     *
     * @param array $args
     *
     * @since 0.1.0
     * @access public
     *
     * @return Info
     */
    public function info(array $args): Info
    {
        return new Info($this, $args);
    }

    /**
     * Author
     *
     * @since 0.1.0
     * @access public
     *
     * @return Author
     */
    public function author(): Author
    {
        return new Author($this);
    }

    /**
     * Comments
     *
     * @since 0.1.0
     * @access public
     *
     * @return Comments
     */
    public function comments(): Comments
    {
        return new Comments($this);
    }

    /**
     * Get post
     *
     * @since 0.6.0
     * @access public
     *
     * @return WP_Post
     */
    public function get(): WP_Post
    {
        return \get_post($this->id);
    }

    /**
     * Get post type
     *
     * @since 0.1.0
     * @access public
     *
     * @return WP_Post_Type
     */
    public function type(): WP_Post_Type
    {
        return \get_post_type_object($this->get()->post_type);
    }

    /**
     * Post type supports a given feature?
     *
     * @since 0.6.0
     * @access public
     *
     * @return bool
     */
    public function typeSupports(string $feature): bool
    {
        return \post_type_supports($this->get()->post_type, $feature);
    }

    /**
     * Has post got featured image?
     *
     * @since 0.1.0
     * @access public
     *
     * @return bool
     */
    public function hasThumbnail()
    {
        return \has_post_thumbnail($this->get());
    }

    /**
     * Get Post Meta
     *
     * @since 0.1.0
     * @access public
     *
     * @return string|array Post Meta
     */
    public function meta(string $key, bool $single = false)
    {
        return \get_post_meta($this->get()->ID, \sanitize_key($key), $single);
    }

    /**
     * Get Post Excerpt
     *
     * @param int $num Number of characters/words.
     * @param string $more_text Label for more link.
     *
     * @since 0.1.0
     * @access public
     *
     * @return string Post excerpt.
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
     * Get post content.
     *
     * Extract full post content for dispplay,
     * showing the <!--more--> quicktag if set
     *
     * @param boolean $paging Show content navigation links for multipage content?
     * @param string $more_text Label for more link.
     * @param string $stripteaser.
     *
     * @since 0.1.0
     * @access public
     *
     * @return string Post content.
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
     * Get post thumbnail HTML
     *
     * @param string $size The image size.
     * @param array $attr An array of img atrtributes.
     * @param bool $link Link image to post URL?
     *
     * @since 0.1.0
     * @access public
     *
     * @return string
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
     * Get taxonomies for a given post
     *
     * @param integer $post_id
     * @param string $context Hierarchical or not.
     *
     * @since 0.1.0
     * @access public
     *
     * @return array
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
