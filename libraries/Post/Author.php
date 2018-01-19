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

use WP_Error;

/**
 * Post Author
 *
 * @since 0.1.0
 */
final class Author
{
    /**
     * Post
     *
     * @since 0.1.0
     * @access private
     *
     * @var Post $post Post.
     */
    private $post;

    /**
     * Constructor
     *
     * @param Post $post Post.
     *
     * @since 0.1.0
     * @access public
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
        
        if (!\post_type_supports($this->post->wp->post_type, 'author')) {
            return new WP_Error(
                'author_not_supported',
                \esc_html__('Author support not registered for post type.')
            );
        }
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
        
        if ($url = $this->url()) {
            $link .= '<span class="author vcard" itemprop="author" itemscope itemtype="http://schema.org/Person">
                <a rel="author nofollow" class="url fn n" itemprop="url" href="'.\esc_attr($url).'">
                <span itemprop="name">'.$this->displayName().'</span></a>
            </span>';
        } else {
            $link .= '<span class="author vcard" itemprop="author" itemscope itemtype="http://schema.org/Person">
                <span itemprop="name">'.$this->displayName().'</span>
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
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function url(): string
    {
        if ('#' ==
            ($user_url = \get_author_posts_url($this->post->wp->post_author))
        ) {
            return '';
        }

        return $user_url;
    }

    /**
     * Post Author Display Name
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Post author display name.
     */
    private function displayName(): string
    {
        return \get_the_author_meta(
            'display_name',
            $this->post->wp->post_author
        );
    }
}
