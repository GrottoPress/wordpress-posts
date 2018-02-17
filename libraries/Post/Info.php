<?php

/**
 * Post Info
 *
 * @package GrottoPress\WordPress\Post
 * @since 0.1.0
 *
 * @see https://github.com/bradvin/social-share-urls
 *
 * @author GrottoPress <info@grottopress.com>
 * @author N Atta Kusi Adusei
 */

declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use GrottoPress\WordPress\Post\Post;
use GrottoPress\Mobile\Detector;

/**
 * Post Info
 *
 * @since 0.1.0
 */
class Info
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
     * Types of information to retrieve
     *
     * @since 0.1.0
     * @access protected
     *
     * @var array|string $types Info types.
     */
    protected $types;

    /**
     * Separator
     *
     * @since 0.1.0
     * @access protected
     *
     * @var string $separator Separator.
     */
    protected $separator;

    /**
     * Before
     *
     * @since 0.1.0
     * @access protected
     *
     * @var string $before HTML to place before list.
     */
    protected $before;

    /**
     * After
     *
     * @since 0.1.0
     * @access protected
     *
     * @var string $after HTML to place after list.
     */
    protected $after;

    /**
     * Mobile Detector
     *
     * @since 0.1.0
     * @access private
     *
     * @var Detector
     */
    private $mobileDetector;

    /**
     * Constructor
     *
     * @param Post $post Post.
     * @param array $args
     *
     * @since 0.1.0
     * @access public
     */
    public function __construct(Post $post, array $args)
    {
        $this->post = $post;

        $this->mobileDetector = new Detector();

        $this->setArgs($args);
        $this->sanitizeAttributes();
    }

    /**
     * Posts entry meta.
     *
     * Use this to get meta info about post concatenated with
     * a separator.
     *
     * @since 0.1.0
     * @access public
     *
     * @return string A series of post details separated by $this->separator.
     */
    public function list(): string
    {
        if (!$this->types) {
            return '';
        }

        $meta = [];

        foreach ($this->types as $type) {
            if (0 === \stripos($type, 'avatar__')) {
                if (($avatar = $this->post->thumbnail($type))) {
                    $meta[] = $avatar;
                }
            } elseif (0 === \stripos($type, 'updated_ago')) {
                $meta[] = $this->updatedAgo(\preg_replace(
                    '/^updated\_ago(\_\_)?/i',
                    '',
                    $type
                ));
            } elseif (0 === \stripos($type, 'published_ago')) {
                $meta[] = $this->publishedAgo(\preg_replace(
                    '/^published\_ago(\_\_)?/i',
                    '',
                    $type
                ));
            } elseif (\is_callable([$this, ($call = "render_{$type}")])
                && ($return = $this->$call())
            ) {
                $meta[] = $return;
            } elseif (($filter = \apply_filters(
                $type,
                '',
                $this->post->get()->ID,
                $this->separator
            ))) {
                $meta[] = $filter;
            } elseif (($post_meta = $this->post->meta($type, true))) {
                $meta[] = $post_meta;
            } elseif (($terms = $this->termList($type))) {
                $meta[] = $terms;
            }
        }

        if (!$meta) {
            return '';
        }

        return $this->before.\join(
            ' <span class="meta-sep">'.$this->separator.'</span> ',
            $meta
        ).$this->after;
    }

    /**
     * Post Author Link.
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function render_author_name(): string
    {
        if (!($author = $this->post->author())->supported()) {
            return '';
        }

        return $author->name();
    }

    /**
     * Comments Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Comments count link.
     */
    private function render_comments_link(): string
    {
        if (!($comments = $this->post->comments())->supported()) {
            return '';
        }

        return $comments->link();
    }

    /**
     * Post Updated Date.
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Post updated date.
     */
    private function render_updated_date(): string
    {
        return '<time class="updated entry-date" itemprop="dateModified" datetime="'
            .\esc_attr(\get_the_modified_time('Y-m-d\TH:i:sO', '', $this->post->get())).'">'
            .\get_the_modified_time(\get_option('date_format'), '', $this->post->get())
        .'</time>';
    }
    
    /**
     * Post Updated Time.
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Post updated time
     */
    private function render_updated_time(): string
    {
        return '<time class="updated entry-date" itemprop="dateModified" datetime="'
            .\esc_attr(\get_the_modified_time(
                'Y-m-d\TH:i:sO',
                '',
                $this->post->get()
            )).'">'.\get_the_modified_time(
                \get_option('time_format'),
                '',
                $this->post->get()
            ).
        '</time>';
    }
    
    /**
     * Post Published Date.
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Post published date.
     */
    private function render_published_date(): string
    {
        return '<time class="published entry-date" itemprop="datePublished" datetime="'.
           \esc_attr(\get_the_date('Y-m-d\TH:i:sO', $this->post->get())).'">'.
           \get_the_date(\get_option('date_format'), $this->post->get()).
        '</time>';
    }
    
    /**
     * Post Published Time.
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Post published time
     */
    private function render_published_time(): string
    {
        return '<time class="published entry-date" itemprop="datePublished" datetime="'.
           \esc_attr(\get_the_date('Y-m-d\TH:i:sO', $this->post->get())).'">'.
           \get_the_time(\get_option('time_format'), $this->post->get()).
        '</time>';
    }

    /**
     * Categories
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Category links.
     */
    private function render_category_list(): string
    {
        if (!\has_category('', $this->post->get())) {
            return '';
        }
        
        return '<span class="category-links"><span class="meta-title">'.
            \esc_html__('Categories:').
        '</span> <span itemprop="articleSection">'.
            \get_the_category_list(', ', '', $this->post->get()->ID).
        '</span></span>';
    }

    /**
     * Tags
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Tag links.
     */
    private function render_tag_list(): string
    {
        if (!\has_tag('', $this->post->get())) {
            return '';
        }
        
        return \get_the_tag_list(
            '<span class="tag-links"><span class="meta-title">'.
                \esc_html__('Tags: ').'</span> <span itemprop="keywords">',
            ', ',
            '</span></span>',
            $this->post->get()->ID
        );
    }

    /**
     * Edit Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Edit link.
     */
    private function render_edit_link(): string
    {
        if (!\current_user_can(
            $this->post->type()->cap->edit_post,
            $this->post->get()->ID
        )) {
            return '';
        }
        
        return '<a class="edit-post-link" href="'.
            \get_edit_post_link($this->post->get()->ID).
            '"  itemprop="url">'.\esc_html__('Edit').'</a>';
    }
    
    /**
     * Delete Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Delete link.
     */
    private function render_delete_link(): string
    {
        if (!\current_user_can(
            $this->post->type()->cap->delete_post,
            $this->post->get()->ID
        )) {
            return '';
        }
        
        return '<a class="delete-post-link" onclick="return confirm(\''.
            \sprintf(
                \esc_html__('Delete %s?'),
                \esc_attr(\get_the_title($this->post->get()))
            ).
            '\')" href="'.\esc_attr(\get_delete_post_link($this->post->get()->ID)).
            '"  itemprop="url">'.\esc_html__('Delete').'</a>';
    }
    
    /**
     * Post Type
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Post type.
     */
    private function render_post_type(): string
    {
        return '<span class="post-type">'.
            $this->post->type()->labels->singular_name.'</span>';
    }
    
    /**
     * Tweet Buttom
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Tweet button
     */
    private function render_tweet_button(): string
    {
        return '<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.
            \wp_get_shortlink($this->post->get()->ID).'" data-text="'.
            \esc_attr(\sanitize_text_field(\get_the_title($this->post->get()))).
            '" data-via="" data-count="horizontal">Tweet</a>'.

            '<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?"http":"https";if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document, "script", "twitter-wjs");</script>';
    }
    
    /**
     * PlusOne Buttom
     *
     * @since 0.1.0
     * @access private
     *
     * @return string PlusOne button.
     */
    private function render_plusone_button(): string
    {
        \wp_enqueue_script('plusone', 'https://apis.google.com/js/platform.js');
        
        return '<div class="plusone" data-size="medium" data-href="'.
            \wp_get_shortlink($this->post->get()->ID).'"></div>';
    }

    /**
     * Google+ Share Buttom
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Google+ share button
     */
    private function render_googleshare_button(): string
    {
        \wp_enqueue_script('plusone', 'https://apis.google.com/js/platform.js');
        
        return '<div class="g-plus" data-action="share" data-size="medium" data-href="'.
            \wp_get_shortlink($this->post->get()->ID).'"></div>';
    }
    
    /**
     * ShareThis Buttom
     *
     * @since 0.1.0
     * @access private
     *
     * @return string ShareThis button
     */
    private function render_sharethis_button(): string
    {
        \wp_enqueue_script(
            'sharethis',
            'https://ws.sharethis.com/button/buttons.js'
        );

        return '<span class="st_sharethis_hcount" st_url="'.
            \wp_get_shortlink($this->post->get()->ID).'" st_title="'.
            \esc_attr(\sanitize_text_field(\get_the_title($this->post->get()))).
            '" st_summary="'.\esc_attr($this->post->excerpt()).
            '" st_via=""></span>';
    }
    
    /**
     * Facebook Share Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Facebook share link.
     */
    private function render_share_link(): string
    {
        return '<a class="facebook-link social-link share-link" rel="external nofollow noopener" href="https://www.facebook.com/sharer/sharer.php?u='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&display=popup" target="_blank" itemprop="url"><i class="fa fa-facebook-official" aria-hidden="true"></i> '.
            \esc_html__('Share').'</a>';
    }

    /**
     * Tweet Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Tweet link
     */
    private function render_tweet_link(): string
    {
        $username = \sanitize_title(\apply_filters(
            'grotto_wp_post_twitter_username',
            ''
        ));

        $via = $username ? '&via='.$username : '';
        
        return '<a class="tweet-link social-link share-link" rel="external nofollow noopener" href="https://twitter.com/intent/tweet'.
            '?text='.\urlencode_deep(\get_the_title($this->post->get())).
            '&url='.\urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            $via.'" target="_blank" itemprop="url"><i class="fa fa-twitter" aria-hidden="true"></i> '.
            \esc_html__('Tweet').'</a>';
    }

    /**
     * Google+ Share Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Google+ share link
     */
    private function render_googleplus_link(): string
    {
        return '<a class="googleplus-link social-link share-link" rel="external nofollow noopener" href="https://plus.google.com/share?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fa fa-google-plus-official" aria-hidden="true"></i> '.
            \esc_html__('Google+').'</a>';
    }

    /**
     * Pinterest Pin Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Pinterest pin link
     */
    private function render_pin_link(): string
    {
        return '<a class="pinterest-link social-link share-link" rel="external nofollow noopener" href="https://pinterest.com/pin/create/bookmarklet/?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&media='.\urlencode_deep(\wp_get_attachment_url(\get_post_thumbnail_id($this->post->get()))).
            '&description='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fa fa-pinterest" aria-hidden="true"></i> '.
            \esc_html__('Pin').'</a>';
    }

    /**
     * LinkedIn Share Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string LinkedIn share link
     */
    private function render_linkedin_link(): string
    {
        return '<a class="linkedin-link social-link share-link" rel="external nofollow noopener" href="https://www.linkedin.com/shareArticle?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&title='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fa fa-linkedin" aria-hidden="true"></i> '.
            \esc_html__('LinkedIn').'</a>';
    }

    /**
     * Buffer Add Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Buffer share link
     */
    private function render_buffer_link(): string
    {
        return '<a class="buffer-link social-link share-link" rel="external nofollow noopener" href="https://buffer.com/add?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&text='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fa fa-share-alt" aria-hidden="true"></i> '.
            \esc_html__('Buffer').'</a>';
    }

    /**
     * Digg Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Digg link
     */
    private function render_digg_link(): string
    {
        return '<a class="digg-link social-link share-link" rel="external nofollow noopener" href="https://digg.com/submit?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&title='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fa fa-digg" aria-hidden="true"></i> '.
            \esc_html__('Digg').'</a>';
    }

    /**
     * Tumblr Share Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Tumblr share link
     */
    private function render_tumblr_link(): string
    {
        return '<a class="tumblr-link social-link share-link" rel="external nofollow noopener" href="https://www.tumblr.com/widgets/share/tool?canonicalUrl='.
        \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
        '&title='.\urlencode_deep(\get_the_title($this->post->get())).
        '&caption='.\urlencode_deep(\get_the_excerpt($this->post->get())).
        '" target="_blank" itemprop="url"><i class="fa fa-tumblr" aria-hidden="true"></i> '.
        \esc_html__('Tumblr').'</a>';
    }

    /**
     * Reddit Share Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Reddit link
     */
    private function render_reddit_link(): string
    {
        return '<a class="reddit-link social-link share-link" rel="external nofollow noopener" href="https://reddit.com/submit?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&title='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fa fa-reddit" aria-hidden="true"></i> '.
            \esc_html__('Reddit').'</a>';
    }

    /**
     * Delicious Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Delicious link
     */
    // private function render_delicious_link(): string
    //{
    //     return '<a class="delicious-link social-link share-link" rel="external nofollow noopener" href="https://delicious.com/save?url='.\urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).'&title='.\urlencode_deep(\get_the_title($this->post->get())).'&v=5&provider='.\urlencode_deep(\get_bloginfo('name')).'&noui&jump=close" target="_blank" itemprop="url"><i class="fa fa-delicious" aria-hidden="true"></i> '.\esc_html__('Delicious').'</a>';
    // }

    /**
     * Blogger Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Blogger link
     */
    private function render_blogger_link(): string
    {
        return '<a class="blogger-link social-link share-link" rel="external nofollow noopener" href="https://www.blogger.com/blog-this.g?u='.
        \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
        '&n='.\urlencode_deep(\get_the_title($this->post->get())).
        '&t='.\urlencode_deep(\get_the_excerpt($this->post->get())).
        '" target="_blank" itemprop="url"><i class="fa fa-share-alt" aria-hidden="true"></i> '.
        \esc_html__('Blogger').'</a>';
    }

    /**
     * Pocket Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Pocket link
     */
    private function render_pocket_link(): string
    {
        return '<a class="pocket-link social-link share-link" rel="external nofollow noopener" href="https://getpocket.com/save?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fa fa-get-pocket" aria-hidden="true"></i> '.
            \esc_html__('Pocket').'</a>';
    }

    /**
     * Skype Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Skype link
     */
    private function render_skype_link(): string
    {
        return '<a class="skype-link social-link share-link" rel="external nofollow noopener" href="https://web.skype.com/share?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fa fa-skype" aria-hidden="true"></i> '.
            \esc_html__('Skype').'</a>';
    }

    /**
     * Viber Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Viber link
     */
    private function render_viber_link(): string
    {
        if (!$this->mobileDetector->isSmart()) {
            return '';
        }

        return '<a class="viber-link social-link share-link" rel="external nofollow" href="viber://forward?text='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" itemprop="url"><i class="fa fa-share-alt" aria-hidden="true"></i> '.
            \esc_html__('Viber').'</a>';
    }

    /**
     * WhatsApp Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string WhatsApp link
     */
    private function render_whatsapp_link(): string
    {
        if (!$this->mobileDetector->isSmart()) {
            return '';
        }

        return '<a class="whatsapp-link social-link share-link" rel="external nofollow" href="whatsapp://send?text='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" itemprop="url"><i class="fa fa-whatsapp" aria-hidden="true"></i> '.
            \esc_html__('WhatsApp').'</a>';
    }

    /**
     * Telegram Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Telegram link
     */
    private function render_telegram_link(): string
    {
        if (!$this->mobileDetector->isSmart()) {
            return '';
        }

        return '<a class="telegram-link social-link share-link" rel="external nofollow" href="tg://msg_url?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&text='.\urlencode_deep(\get_the_title($this->post->get())).
            '" itemprop="url"><i class="fa fa-telegram" aria-hidden="true"></i> '.
            \esc_html__('Telegram').'</a>';
    }

    /**
     * VK Link
     *
     * @since 0.1.0
     * @access private
     *
     * @return string VK link
     */
    private function render_vk_link(): string
    {
        return '<a class="vk-link social-link share-link" rel="external nofollow noopener" href="https://vk.com/share.php?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fa fa-vk" aria-hidden="true"></i> '.
            \esc_html__('VK').'</a>';
    }

    /**
     * Time since post was updated.
     *
     * @var string $format Show actual time or time difference?
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Time since post was updated.
     */
    private function updatedAgo(string $format): string
    {
        return '<time class="updated entry-date" itemprop="dateModified" datetime="'.
            \esc_attr(\get_the_modified_time(
                'Y-m-d\TH:i:sO',
                '',
                $this->post->get()
            )).
            '">'.$this->post->time('updated')->render($format)
        .'</time>
        </span>';
    }

    /**
     * Time since post was published.
     *
     * @var string $format Show actual time or time difference?
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Time since post was updated.
     */
    private function publishedAgo(string $format): string
    {
        return '<time class="published entry-date" itemprop="dateModified" datetime="'.
            \esc_attr(\get_the_date('Y-m-d\TH:i:sO', $this->post->get())).'">'.
            $this->post->time('published')->render($format).
        '</time>';
    }

    /**
     * Get term list
     *
     * @since 0.1.0
     * @access private
     *
     * @return string Terms
     */
    private function termList(string $taxonomy): string
    {
        $taxonomy = \sanitize_key($taxonomy);

        if (!\taxonomy_exists($taxonomy)) {
            return '';
        }

        $terms = \get_the_terms($this->post->get(), $taxonomy);

        if (!$terms || \is_wp_error($terms)) {
            return '';
        }

        $tax_name = \count($terms) > 1
            ? \get_taxonomy($taxonomy)->labels->name
            : \get_taxonomy($taxonomy)->labels->singular_name;

        return \get_the_term_list(
            $this->post->get()->ID,
            $taxonomy,
            '<span class="term-links"><span class="meta-title">'.
                $tax_name.':</span> ',
            ', ',
            '</span>'
        );
    }

    /**
     * Set Args
     *
     * @param array $args
     *
     * @since 0.1.0
     * @access private
     */
    private function setArgs(array $args)
    {
        if (!($vars = \get_object_vars($this))) {
            return;
        }

        unset($vars['post']);
        unset($vars['mobileDetector']);

        foreach ($vars as $key => $value) {
            $this->$key = $args[$key] ?? '';
        }
    }

    /**
     * Sanitize attributes
     *
     * @since 0.1.0
     * @access private
     */
    private function sanitizeAttributes()
    {
        $this->separator = $this->separator ? \esc_attr(
            $this->separator
        ) : '|';
        $this->types = !empty($this->types[0]) ? \array_map(
            'sanitize_key',
            $this->types
        ) : [];
    }
}
