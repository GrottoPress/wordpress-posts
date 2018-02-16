<?php

/**
 * Post Author
 *
 * @package GrottoPress\WordPress\Post
 * @since 0.1.0
 *
 * @author GrottoPress <info@grottopress.com>
 * @author N Atta Kusi Adusei
 */

declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

/**
 * Post Author
 *
 * @since 0.1.0
 */
class Author
{
    /**
     * Post
     *
     * @since 0.1.0
     * @access private
     *
     * @var Post
     */
    private $post;

    /**
     * Constructor
     *
     * @param Post $post
     *
     * @since 0.1.0
     * @access public
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Is comments supported?
     *
     * @since 0.6.0
     * @access public
     *
     * @return bool
     */
    public function supported(): bool
    {
        return $this->post->typeSupports('author');
    }

    /**
     * Post author link
     *
     * Link the author name to author page if URL is set,
     * or just return the author's name.
     *
     * @param string $before Text to preppend to link.
     * @param string $after Text to append to link.
     *
     * @since 0.1.0
     * @access public
     *
     * @return string Author name, linked to author page.
     */
    public function name(string $before = '', string $after = ''): string
    {
        $link = '';

        if ($before) {
            $link .= '<span class="before-author-link">'.
                \esc_attr($before).
            '</span> ';
        }

        if ($url = $this->postsUrl()) {
            $link .= '<span class="author vcard" itemprop="author" itemscope itemtype="http://schema.org/Person">
                <a rel="author nofollow" class="url fn n" itemprop="url" href="'.\esc_attr($url).'">
                <span itemprop="name">'.$this->meta('display_name').'</span></a>
            </span>';
        } else {
            $link .= '<span class="author vcard" itemprop="author" itemscope itemtype="http://schema.org/Person">
                <span itemprop="name">'.$this->meta('display_name').'</span>
            </span>';
        }

        if ($after) {
            $link .= '<span class="after-author-link">'.\esc_attr($after).'</span> ';
        }

        return $link;
    }

    /**
     * Get post author url
     *
     * @since 0.6.0 Renamed from `url()` to `postsUrl()`
     * @since 0.1.0
     *
     * @access public
     *
     * @return string
     */
    public function postsUrl(): string
    {
        if ('#' === (
            $url = \get_author_posts_url($this->post->get()->post_author)
        )) {
            return '';
        }

        return $url;
    }

    /**
     * Get author meta
     *
     * @since 0.6.0
     * @access public
     *
     * @return mixed The author meta.
     */
    public function meta(string $meta)
    {
        return \get_the_author_meta($meta, $this->post->get()->post_author);
    }
}
