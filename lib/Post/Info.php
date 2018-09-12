<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use GrottoPress\WordPress\Post;
use GrottoPress\Mobile\Detector;

class Info
{
    /**
     * @var Post
     */
    private $post;

    /**
     * @var array
     */
    protected $types;

    /**
     * @var string
     */
    protected $separator;

    /**
     * @var string $before HTML to place before list.
     */
    protected $before;

    /**
     * @var string $after HTML to place after list.
     */
    protected $after;

    /**
     * @var Detector
     */
    private $mobileDetector;

    public function __construct(Post $post, Detector $detector, array $args)
    {
        $this->post = $post;
        $this->mobileDetector = $detector;

        $this->setArgs($args);
        $this->sanitizeAttributes();
    }

    /**
     * Get post info, concatenated with a separator.
     */
    public function list(): string
    {
        if (!$this->types) {
            return '';
        }

        $meta = [];

        foreach ($this->types as $type) {
            if (0 === \stripos($type, 'avatar__')) {
                if ($this->post->author()->supported() &&
                    ($avatar = $this->post->thumbnail($type))
                ) {
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
            } elseif ($filter = \apply_filters(
                $type,
                '',
                $this->post->get()->ID,
                $this->separator
            )) {
                $meta[] = $filter;
            } elseif ($post_meta = $this->post->meta($type, true)) {
                $meta[] = $post_meta;
            } elseif ($terms = $this->termList($type)) {
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
     * Called if $type === 'author_name'
     */
    private function render_author_name(): string
    {
        if (!($author = $this->post->author())->supported()) {
            return '';
        }

        return $author->name();
    }

    /**
     * Called if $type === 'comments_link'
     */
    private function render_comments_link(): string
    {
        if (!($comments = $this->post->comments())->supported()) {
            return '';
        }

        return $comments->link();
    }

    /**
     * Called if $type === 'updated_date'
     */
    private function render_updated_date(): string
    {
        return '<time class="updated entry-date" itemprop="dateModified" datetime="'
            .\esc_attr(\get_the_modified_time('Y-m-d\TH:i:sO', '', $this->post->get())).'">'
            .\get_the_modified_time(\get_option('date_format'), '', $this->post->get())
        .'</time>';
    }

    /**
     * Called if $type === 'updated_time'
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
     * Called if $type === 'published_date'
     */
    private function render_published_date(): string
    {
        return '<time class="published entry-date" itemprop="datePublished" datetime="'.
           \esc_attr(\get_the_date('Y-m-d\TH:i:sO', $this->post->get())).'">'.
           \get_the_date(\get_option('date_format'), $this->post->get()).
        '</time>';
    }

    /**
     * Called if $type === 'published_time'
     */
    private function render_published_time(): string
    {
        return '<time class="published entry-date" itemprop="datePublished" datetime="'.
           \esc_attr(\get_the_date('Y-m-d\TH:i:sO', $this->post->get())).'">'.
           \get_the_time(\get_option('time_format'), $this->post->get()).
        '</time>';
    }

    /**
     * Called if $type === 'category_list'
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
     * Called if $type === 'tag_list'
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
     * Called if $type === 'edit_link'
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
     * Called if $type === 'delete_link'
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
     * Called if $type === 'post_type'
     */
    private function render_post_type(): string
    {
        return '<span class="post-type">'.
            $this->post->type()->labels->singular_name.'</span>';
    }

    /**
     * Called if $type === 'tweet_button'
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
     * Called if $type === 'plusone_button'
     */
    private function render_plusone_button(): string
    {
        \wp_enqueue_script('plusone', 'https://apis.google.com/js/platform.js');

        return '<div class="plusone" data-size="medium" data-href="'.
            \wp_get_shortlink($this->post->get()->ID).'"></div>';
    }

    /**
     * Called if $type === 'googleshare_button'
     */
    private function render_googleshare_button(): string
    {
        \wp_enqueue_script('plusone', 'https://apis.google.com/js/platform.js');

        return '<div class="g-plus" data-action="share" data-size="medium" data-href="'.
            \wp_get_shortlink($this->post->get()->ID).'"></div>';
    }

    /**
     * Called if $type === 'sharethis_button'
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
     * Called if $type === 'share_link'
     */
    private function render_share_link(): string
    {
        return '<a class="facebook-link social-link share-link" rel="external nofollow noopener" href="https://www.facebook.com/sharer/sharer.php?u='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&display=popup" target="_blank" itemprop="url"><i class="fab fa-facebook fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Share').'</a>';
    }

    /**
     * Called if $type === 'tweet_link'
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
            $via.'" target="_blank" itemprop="url"><i class="fab fa-twitter fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Tweet').'</a>';
    }

    /**
     * Called if $type === 'googleplus_link'
     */
    private function render_googleplus_link(): string
    {
        return '<a class="googleplus-link social-link share-link" rel="external nofollow noopener" href="https://plus.google.com/share?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fab fa-google-plus fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Google+').'</a>';
    }

    /**
     * Called if $type === 'pin_link'
     */
    private function render_pin_link(): string
    {
        return '<a class="pinterest-link social-link share-link" rel="external nofollow noopener" href="https://pinterest.com/pin/create/bookmarklet/?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&media='.\urlencode_deep(\wp_get_attachment_url(\get_post_thumbnail_id($this->post->get()))).
            '&description='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fab fa-pinterest fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Pin').'</a>';
    }

    /**
     * Called if $type === 'linkedin_link'
     */
    private function render_linkedin_link(): string
    {
        return '<a class="linkedin-link social-link share-link" rel="external nofollow noopener" href="https://www.linkedin.com/shareArticle?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&title='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fab fa-linkedin fa-sm" aria-hidden="true"></i> '.
            \esc_html__('LinkedIn').'</a>';
    }

    /**
     * Called if $type === 'buffer_link'
     */
    private function render_buffer_link(): string
    {
        return '<a class="buffer-link social-link share-link" rel="external nofollow noopener" href="https://buffer.com/add?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&text='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fas fa-share fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Buffer').'</a>';
    }

    /**
     * Called if $type === 'digg_link'
     */
    private function render_digg_link(): string
    {
        return '<a class="digg-link social-link share-link" rel="external nofollow noopener" href="https://digg.com/submit?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&title='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fab fa-digg fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Digg').'</a>';
    }

    /**
     * Called if $type === 'tumblr_link'
     */
    private function render_tumblr_link(): string
    {
        return '<a class="tumblr-link social-link share-link" rel="external nofollow noopener" href="https://www.tumblr.com/widgets/share/tool?canonicalUrl='.
        \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
        '&title='.\urlencode_deep(\get_the_title($this->post->get())).
        '&caption='.\urlencode_deep(\get_the_excerpt($this->post->get())).
        '" target="_blank" itemprop="url"><i class="fab fa-tumblr fa-sm" aria-hidden="true"></i> '.
        \esc_html__('Tumblr').'</a>';
    }

    /**
     * Called if $type === 'reddit_link'
     */
    private function render_reddit_link(): string
    {
        return '<a class="reddit-link social-link share-link" rel="external nofollow noopener" href="https://reddit.com/submit?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&title='.\urlencode_deep(\get_the_title($this->post->get())).
            '" target="_blank" itemprop="url"><i class="fab fa-reddit fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Reddit').'</a>';
    }

    /**
     * Called if $type === 'delicious_link'
     *
     * Disabled cuz URL does not seem to work.
     */
    // private function render_delicious_link(): string
    // {
    //     return '<a class="delicious-link social-link share-link" rel="external nofollow noopener" href="https://delicious.com/save?url='.\urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).'&title='.\urlencode_deep(\get_the_title($this->post->get())).'&v=5&provider='.\urlencode_deep(\get_bloginfo('name')).'&noui&jump=close" target="_blank" itemprop="url"><i class="fab fa-delicious fa-sm" aria-hidden="true"></i> '.\esc_html__('Delicious').'</a>';
    // }

    /**
     * Called if $type === 'blogger_link'
     */
    private function render_blogger_link(): string
    {
        return '<a class="blogger-link social-link share-link" rel="external nofollow noopener" href="https://www.blogger.com/blog-this.g?u='.
        \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
        '&n='.\urlencode_deep(\get_the_title($this->post->get())).
        '&t='.\urlencode_deep(\get_the_excerpt($this->post->get())).
        '" target="_blank" itemprop="url"><i class="fab fa-blogger fa-sm" aria-hidden="true"></i> '.
        \esc_html__('Blogger').'</a>';
    }

    /**
     * Called if $type === 'pocket_link'
     */
    private function render_pocket_link(): string
    {
        return '<a class="pocket-link social-link share-link" rel="external nofollow noopener" href="https://getpocket.com/save?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fab fa-get-pocket fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Pocket').'</a>';
    }

    /**
     * Called if $type === 'skype_link'
     */
    private function render_skype_link(): string
    {
        return '<a class="skype-link social-link share-link" rel="external nofollow noopener" href="https://web.skype.com/share?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fab fa-skype fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Skype').'</a>';
    }

    /**
     * Called if $type === 'viber_link'
     */
    private function render_viber_link(): string
    {
        if (!$this->mobileDetector->isSmart()) {
            return '';
        }

        return '<a class="viber-link social-link share-link" rel="external nofollow" href="viber://forward?text='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" itemprop="url"><i class="fab fa-viber fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Viber').'</a>';
    }

    /**
     * Called if $type === 'whatsapp_link'
     */
    private function render_whatsapp_link(): string
    {
        if (!$this->mobileDetector->isSmart()) {
            return '';
        }

        return '<a class="whatsapp-link social-link share-link" rel="external nofollow" href="whatsapp://send?text='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" itemprop="url"><i class="fab fa-whatsapp fa-sm" aria-hidden="true"></i> '.
            \esc_html__('WhatsApp').'</a>';
    }

    /**
     * Called if $type === 'telegram_link'
     */
    private function render_telegram_link(): string
    {
        if (!$this->mobileDetector->isSmart()) {
            return '';
        }

        return '<a class="telegram-link social-link share-link" rel="external nofollow" href="tg://msg_url?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '&text='.\urlencode_deep(\get_the_title($this->post->get())).
            '" itemprop="url"><i class="fab fa-telegram fa-sm" aria-hidden="true"></i> '.
            \esc_html__('Telegram').'</a>';
    }

    /**
     * Called if $type === 'vk_link'
     */
    private function render_vk_link(): string
    {
        return '<a class="vk-link social-link share-link" rel="external nofollow noopener" href="https://vk.com/share.php?url='.
            \urlencode_deep(\wp_get_shortlink($this->post->get()->ID)).
            '" target="_blank" itemprop="url"><i class="fab fa-vk fa-sm" aria-hidden="true"></i> '.
            \esc_html__('VK').'</a>';
    }

    /**
     * Time since post was updated.
     *
     * @param string $format Show actual time or time difference?
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
     */
    private function publishedAgo(string $format): string
    {
        return '<time class="published entry-date" itemprop="dateModified" datetime="'.
            \esc_attr(\get_the_date('Y-m-d\TH:i:sO', $this->post->get())).'">'.
            $this->post->time('published')->render($format).
        '</time>';
    }

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

    private function setArgs(array $args)
    {
        if (!($vars = \get_object_vars($this))) {
            return;
        }

        unset($vars['post']);
        unset($vars['mobileDetector']);

        foreach ($vars as $key => $value) {
            $this->$key = $args[$key] ?? null;
        }
    }

    private function sanitizeAttributes()
    {
        $this->separator = $this->separator ? \esc_attr(
            $this->separator
        ) : '|';

        $this->types = !empty($this->types[0]) ? \array_map(
            'sanitize_key',
            $this->types
        ) : [];

        $this->before = \is_string($this->before) ? $this->before : '';
        $this->after = \is_string($this->after) ? $this->after : '';
    }
}
