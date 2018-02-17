<?php

/**
 * Post Comments
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
 * Post Comments
 *
 * @since 0.1.0
 */
class Comments
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
    }

    /**
     * Comments count
     *
     * @since 0.6.0
     * @access public
     *
     * @return int
     */
    public function count(): int
    {
        return (int)\get_comments_number($this->post->get());
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
        return $this->post->typeSupports('comments');
    }

    /**
     * Comments link
     *
     * Link the author name to author page if URL is set,
     * or just return the author's name if no URL is provided.
     *
     * @since 0.1.0
     * @access public
     *
     * @return string Author, linked to author page.
     */
    public function link(): string
    {
        if (\post_password_required($this->post->get())
            || !\comments_open($this->post->get())
        ) {
            return $this->text();
        }

        return '<a class="comments-link post-'.$this->post->get()->ID.
            '-comments-link" itemprop="discussionUrl" href="'.
            \esc_attr(\get_comments_link($this->post->get())).'">'.
            $this->text().'</a>';
    }

    /**
     * Comments text
     *
     * Retrieves comments message based on comment count.
     *
     * @since 0.1.0
     * @access public
     *
     * @return string Comments text.
     */
    public function text(): string
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

    /**
     * No Comments text
     *
     * @sinc 0.1.0
     * @access private
     *
     * @return string No comments text.
     */
    private function noCommentsText(): string
    {
        return \apply_filters(
            'grotto_wp_post_no_comments_text',
            \esc_html__('Leave a comment'),
            $this->count()
        );
    }

    /**
     * 1 Comment text
     *
     * @since 0.1.0
     * @access private
     *
     * @return string One comment text.
     */
    private function oneCommentText(): string
    {
        return \apply_filters(
            'grotto_wp_post_one_comment_text',
            \sprintf(
                \esc_html__('%s comment'),
                '<span class="comments-number" itemprop="commentCount">1</span>'
            ),
            $this->count()
        );
    }

    /**
     * More Comments text
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Comments text to display for posts with more than 1 comment
     */
    private function moreCommentsText(): string
    {
        return \apply_filters(
            'grotto_wp_post_more_comments_text',
            \sprintf(
                \esc_html__('%s comments'),
                '<span class="comments-number" itemprop="commentCount">'.\number_format_i18n($this->count).
                    '</span>'
            ),
            $this->count()
        );
    }

    /**
     * Comments Closed text
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Comments text to display for posts with comments closed.
     */
    private function commentsClosedText(): string
    {
        return \apply_filters(
            'grotto_wp_post_more_comments_text',
            \esc_html__('Comments closed'),
            $this->count()
        );
    }
}
