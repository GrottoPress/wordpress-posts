<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use GrottoPress\WordPress\Post;

class Author
{
    /**
     * @var Post
     */
    private $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function supported(): bool
    {
        return $this->post->typeSupports('author');
    }

    /**
     * @param string $before Text to preppend to link.
     * @param string $after Text to append to link.
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
            $link .= '<span class="author">
                <a rel="author nofollow" class="url" href="'.
                    \esc_attr($url).'">'
                .$this->meta('display_name').'</a>
            </span>';
        } else {
            $link .= '<span class="author">'.
                $this->meta('display_name').
            '</span>';
        }

        if ($after) {
            $link .= '<span class="after-author-link">'.\esc_attr($after).'</span> ';
        }

        return $link;
    }

    public function postsURL(): string
    {
        if ('#' === (
            $url = \get_author_posts_url($this->post->get()->post_author)
        )) {
            return '';
        }

        return $url;
    }

    public function meta(string $key)
    {
        return \get_the_author_meta($key, $this->post->get()->post_author);
    }
}
