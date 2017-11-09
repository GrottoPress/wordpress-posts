<?php

/**
 * Posts Loop
 *
 * @package GrottoPress\WordPress\Posts
 * @since 0.1.0
 *
 * @author GrottoPress <info@grottopress.com>
 * @author N Atta Kus Adusei
 */

declare (strict_types = 1);

namespace GrottoPress\WordPress\Posts;

use GrottoPress\WordPress\Post\Post;
use WP_Query;

/**
 * Posts Loop
 *
 * @since 0.1.0
 */
final class Loop
{
    /**
     * Posts
     *
     * @since 0.1.0
     * @access private
     *
     * @var Posts $posts Posts.
     */
    private $posts;
    
    /**
     * Constructor
     *
     * @param Posts $posts WordPress posts.
     *
     * @since 0.1.0
     * @access public
     */
    public function __construct(Posts $posts)
    {
        $this->posts = $posts;
    }

    /**
     * Do the loop
     *
     * @since 0.1.0
     * @access public
     *
     * @return string Posts HTML.
     */
    public function run(): string
    {
        $query = new WP_Query($this->posts->args['wp_query']);

        /**
         * @action grotto_query_posts_loop_before
         *
         * @var WP_Query $query WordPress query.
          *
         * @since 0.1.0
         */
        \do_action('grotto_wp_posts_loop_before', $query, $this->posts->args);

        $out = '';
        
        if ($query->have_posts()) {
            $count = 0;
            
            $out .= $this->openWrapTag();
            $out .= $this->posts->pagination->render('top', $query);
            
            while ($query->have_posts()) {
                $query->the_post();
                $count++;
                
                $out .= $this->filter('before', \get_the_ID(), $count);
                $out .= $this->loopBody(\get_the_ID(), $count);
                $out .= $this->filter('after', \get_the_ID(), $count);
            }
            
            $out .= $this->posts->pagination->render('bottom', $query);
            $out .= $this->closeWrapTag();
        }

        /**
         * @filter grotto_query_posts_loop
         *
         * @var string $out Loop output.
         * @var WP_Query $query WordPress query.
         *
         * @since 0.1.0
         */
        $out = \apply_filters(
            'grotto_wp_posts_loop',
            $out,
            $query,
            $this->posts->args
        );
        
        \wp_reset_postdata();
        
        return $out;
    }

    /**
     * Posts markup: start
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function openWrapTag(): string
    {
        $out = '';

        if (!($wrap_tag = \sanitize_key($this->posts->args['tag']))) {
            return $out;
        }

        $out .= '<'.$wrap_tag.'';
        
        $out .=' class="posts-wrap'.($this->posts->args['class']
            ? ' '.$this->posts->args['class'] : '').'"';
        
        if ($this->posts->args['id']) {
            $out .= ' id="'.\sanitize_title($this->posts->args['id']).'"';
        }
        
        $out .= '>';
        
        return $out;
    }
    
    /**
     * Posts markup: end
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function closeWrapTag(): string
    {
        if (!($wrap_tag = \sanitize_key($this->posts->args['tag']))) {
            return '';
        }

        return '</'.$wrap_tag.'><!-- .posts-wrap -->';
    }
    
    /**
     * Apply filters before/after post
     *
     * @filter grotto_wp_posts_post_{$context}
     *
     * @param string $context 'before' or 'after' post.
     * @param int $post_id Post ID.
     * @param int $count Current post number/count.
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function filter(
        string $context,
        int $post_id,
        int $count
    ): string {
        return \apply_filters(
            "grotto_wp_posts_post_{$context}",
            '',
            $post_id,
            $count,
            $this->posts->args
        );
    }
    
    /**
     * Body of single post
     *
     * Contains the logic to display a single post
     * within the while have_posts loop
     *
     * @param int $post_id Post ID.
     * @param int $count Current post number/count.
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function loopBody(int $post_id, int $count): string
    {
        $post = $this->posts->post($post_id);
        
        return
        
        $this->openPostTag($post, $count).
        $this->thumb('side', $post, $count).
        $this->openHeader($post, $count).
        $this->postInfo('title', 'before', $post, $count).
        $this->title($post, $count).
        $this->postInfo('title', 'after', $post, $count).
        $this->closeHeader($post, $count).
        $this->thumb('top', $post, $count).
        $this->content($post, $count).
        $this->excerpt($post, $count).
        $this->openFooter($post, $count).
        $this->postInfo('excerpt', 'after', $post, $count).
        $this->closeFooter($post, $count).
        $this->closePostTag($post, $count);
    }

    /**
     * Loop body: before title
     *
     * @param string $context 'title' or 'excerpt'
     * @param string $position 'before' or 'after'
     * @param Post $post
     * @param int $count
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function postInfo(
        string $context,
        string $position,
        Post $post,
        int $count
    ): string {
        return $post->info($this->posts->args[$context][$position])->list();
    }

    /**
     * Loop body: Title
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function title(Post $post, int $count): string
    {
        $out = '';
        
        if (0 === ($title_words = $this->posts->args['title']['length'])) {
            return $out;
        }

        if (!($title = \get_the_title($post->wp))) {
            return $out;
        }
        
        $out .= '<'.($title_tag = \sanitize_key(
            $this->posts->args['title']['tag']
        )).' class="entry-title" itemprop="name headline">';
        
        $anchor_title = ($title_words > 0)
            ? ' title="'.\esc_attr(\wp_strip_all_tags($title, true)).'" ' : '';
        
        if (($title_link = $this->posts->args['title']['link'])) {
            $out .= '<a itemprop="url" href="'.\get_permalink($post->wp).
                '" '. $anchor_title.' rel="bookmark">';
        }
        
        if ($title_words < 0) {
            $out .= \sanitize_text_field($title);
        } else {
            $out .= \wp_trim_words(
                $title,
                $title_words,
                '<span class="ellipsis">...</span>'
            );
        }
        
        if ($title_link) {
            $out .= '</a>';
        }
        
        $out .= '</'.$title_tag.'>';
        
        return $out;
    }
    
    /**
     * Loop body: Excerpt
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function excerpt(Post $post, int $count): string
    {
        $out = '';
        
        if ($this->posts->isContent()) {
            return $out;
        }

        $excerpt = $post->excerpt(
            $this->posts->args['excerpt']['length'],
            $this->posts->args['excerpt']['more_text']
        );

        if (!$excerpt) {
            return $out;
        }

        $out .= '<div '.$this->textOffset($post).
            ' class="entry-summary" itemprop="description">';
        $out .= $excerpt;
        $out .= '</div><!-- .entry-summary -->';
        
        return $out;
    }
    
    /**
     * Loop body: Content
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function content(Post $post, int $count): string
    {
        $out = '';
        
        if (!$this->posts->isContent()) {
            return $out;
        }

        $content = $post->content(
            $this->posts->args['excerpt']['more_text'],
            '',
            $this->posts->args['excerpt']['paginate']
        );

        if (!$content) {
            return $out;
        }
        
        $out .= '<div class="entry-content" itemprop="articleBody">';
        $out .= $content;
        $out .= '</div><!-- .entry-content -->';
        
        return $out;
    }
    
    /**
     * Loop body: opening tag
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function openPostTag(Post $post, int $count): string
    {
        $out = '';
        
        if ($this->posts->isList()) {
            $out .= '<li>';
        }
        
        $out .= '<article data-post-id="'.\absint($post->wp->ID).
            '" class="'.\esc_attr($this->postClass($post, $count)).
            '" itemscope itemtype="http://schema.org/Article">';
        
        return $out;
    }
    
    /**
     * Loop body: closing tags
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function closePostTag(Post $post, int $count): string
    {
        $out = '</article>';
        
        if ($this->posts->isList()) {
            $out .= '</li>';
        }
        
        return $out;
    }
    
    /**
     * Loop body: open <header>
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function openHeader(Post $post, int $count): string
    {
        if (!$this->postInfo('title', 'before', $post, $count)
            && !$this->title($post, $count)
            && !$this->postInfo('title', 'after', $post, $count)
        ) {
            return '';
        }
        
        return '<header'.(
            'top' == $this->posts->args['title']['position']
            ? '' : $this->textOffset($post)
        ).'>';
    }
    
    /**
     * Loop body: close </header>
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function closeHeader(Post $post, int $count): string
    {
        if (!$this->title($post, $count)
            && !$this->postInfo('title', 'before', $post, $count)
            && !$this->postInfo('title', 'after', $post, $count)
        ) {
            return '';
        }
        
        return '</header>';
    }

    /**
     * Loop body: open <footer>
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function openFooter(Post $post, int $count): string
    {
        if (!$this->postInfo('excerpt', 'after', $post, $count)) {
            return '';
        }
        
        return '<footer'.$this->textOffset($post).'>';
    }
    
    /**
     * Loop body: close </footer>
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function closeFooter(Post $post, int $count): string
    {
        if (!$this->postInfo('excerpt', 'after', $post, $count)) {
            return '';
        }
        
        return '</footer>';
    }
    
    /**
     * Loop body: Thumb
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function thumb(string $title_pos, Post $post, int $count): string
    {
        if ($title_pos != $this->posts->args['title']['position']
            || !$this->posts->args['image']['size']
        ) {
            return '';
        }

        $attr = [];

        if ($this->posts->args['image']['margin']) {
            $attr['style'] = 'margin:'.\sanitize_text_field(
                \rtrim($this->posts->args['image']['margin']),
                ';'
            ).';';
        }

        return $post->thumbnail(
            $this->posts->args['image']['size'],
            $attr,
            $this->posts->args['image']['link']
        );
    }

    /**
     * Apply text margins
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function textOffset(Post $post): string
    {
        if (($offset = \absint($this->posts->args['text_offset'])) < 1) {
            return '';
        }
        
        if (!$this->posts->args['image']['size'] || !$post->hasThumbnail()) {
            return '';
        }
        
        $text_offset = ' style="';
        
        if ('right' == $this->posts->args['image']['align']) {
            $text_offset .= 'margin-right:'.$offset.'px;';
        } elseif ('left' == $this->posts->args['image']['align']) {
            $text_offset .= 'margin-left:'.$offset.'px;';
        }
        
        $text_offset .= '" ';
        
        return $text_offset;
    }
    
    /**
     * Post classes
     *
     * @since 0.1.0
     * @access private
     *
     * @return string
     */
    private function postClass(Post $post, int $count): string
    {
        if (($count % 2) === 0) {
            $odd_even_class = 'even';
        } else {
            $odd_even_class = 'odd';
        }
        
        $class = \get_post_class(['post-wrap', 'post-count-'.$count, $odd_even_class], $post->wp->ID);
        
        $class[] = ($this->posts->args['image']['size']
            && !$post->hasThumbnail())
            ? 'no-post-thumb' : '';
        
        return \join(' ', $class);
    }
}
