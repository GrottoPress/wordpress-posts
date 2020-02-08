<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use GrottoPress\WordPress\Post;

class Comments
{
    /**
     * @var Post
     */
    private $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function count(): int
    {
        return (int)\get_comments_number($this->post->get());
    }

    public function supported(): bool
    {
        return $this->post->typeSupports('comments');
    }

    public function link(): string
    {
        if (\post_password_required($this->post->get())
            || !\comments_open($this->post->get())
        ) {
            return $this->text();
        }

        return '<a class="comments-link post-'.$this->post->get()->ID.
            '-comments-link" href="'.
            \esc_attr(\get_comments_link($this->post->get())).'">'.
            $this->text().'</a>';
    }

    private function text(): string
    {
        if (\comments_open($this->post->get()) || $this->count() > 0) {
            if ($this->count() < 1) {
                return $this->noCommentsText();
            }

            if ($this->count() > 1) {
                return $this->moreCommentsText();
            }

            return $this->oneCommentText();
        }

        return $this->commentsClosedText();
    }

    private function noCommentsText(): string
    {
        return \apply_filters(
            'grotto_wp_post_no_comments_text',
            \esc_html__('Leave a comment', 'grotto-wp-posts'),
            $this->count()
        );
    }

    private function oneCommentText(): string
    {
        return \apply_filters(
            'grotto_wp_post_one_comment_text',
            \sprintf(
                \esc_html__('%s comment', 'grotto-wp-posts'),
                '<span class="comments-number">1</span>'
            ),
            $this->count()
        );
    }

    private function moreCommentsText(): string
    {
        return \apply_filters(
            'grotto_wp_post_more_comments_text',
            \sprintf(
                \esc_html__('%s comments', 'grotto-wp-posts'),
                '<span class="comments-number">'.
                    \number_format_i18n($this->count()).
                '</span>'
            ),
            $this->count()
        );
    }

    private function commentsClosedText(): string
    {
        return \apply_filters(
            'grotto_wp_post_more_comments_text',
            \esc_html__('Comments closed', 'grotto-wp-posts'),
            $this->count()
        );
    }
}
