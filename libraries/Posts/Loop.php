<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Posts;

use GrottoPress\WordPress\Posts;
use GrottoPress\WordPress\Post;
use WP_Query;

class Loop
{
    /**
     * @var Posts
     */
    private $posts;

    public function __construct(Posts $posts)
    {
        $this->posts = $posts;
    }

    public function run(): string
    {
        $out = '';

        if (0 === $this->posts->args['wp_query']['posts_per_page']) {
            return $out;
        }

        $query = $this->WPQuery($this->posts->args['wp_query']);

        /**
         * @action grotto_query_posts_loop_before
         * @var WP_Query $query
         */
        \do_action('grotto_wp_posts_loop_before', $query, $this->posts->args);

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
         * @var string $out
         * @var WP_Query $query
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

    private function openWrapTag(): string
    {
        $out = '';

        if (!($wrap_tag = \sanitize_key($this->posts->args['tag']))) {
            return $out;
        }

        $out .= '<'.$wrap_tag.'';

        $out .=' class="posts-wrap'.($this->posts->args['class']
            ? ' '.\esc_attr($this->posts->args['class']) : '').'"';

        if ($this->posts->args['id']) {
            $out .= ' id="'.\sanitize_title($this->posts->args['id']).'"';
        }

        $out .= '>';

        return $out;
    }

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
     * @param int $count Current post number/count.
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
     * Contains the logic to display a posts within the
     * `while (have_posts())` loop
     */
    private function loopBody(int $post_id, int $count): string
    {
        $post = $this->post($post_id);

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
     * Loop body: Post info
     *
     * @param string $context 'title' or 'excerpt'
     * @param string $position 'before' or 'after'
     * @param Post $post
     * @param int $count
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
     */
    private function title(Post $post, int $count): string
    {
        $out = '';

        if (0 === ($title_words = $this->posts->args['title']['length'])) {
            return $out;
        }

        if (!($title = \get_the_title($post->get()))) {
            return $out;
        }

        $out .= '<'.($title_tag = \sanitize_key(
            $this->posts->args['title']['tag']
        )).' class="entry-title" itemprop="name headline">';

        $anchor_title = ($title_words > 0)
            ? ' title="'.\esc_attr(\wp_strip_all_tags($title, true)).'" ' : '';

        if ($title_link = $this->posts->args['title']['link']) {
            $out .= '<a itemprop="url" href="'.\get_permalink($post->get()).
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
     */
    private function openPostTag(Post $post, int $count): string
    {
        $out = '';

        if ($this->posts->isList()) {
            $out .= '<li>';
        }

        $out .= '<article data-post-id="'.\absint($post->get()->ID).
            '" class="'.\esc_attr($this->postClass($post, $count)).
            '" itemscope itemtype="http://schema.org/Article">';

        return $out;
    }

    /**
     * Loop body: closing tag
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
            'top' === $this->posts->args['title']['position']
            ? '' : $this->textOffset($post)
        ).'>';
    }

    /**
     * Loop body: close </header>
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

    private function textOffset(Post $post): string
    {
        if (($offset = \absint($this->posts->args['text_offset'])) < 1) {
            return '';
        }

        if (!$this->posts->args['image']['size'] || !$post->hasThumbnail()) {
            return '';
        }

        $text_offset = ' style="';

        if ('right' === $this->posts->args['image']['align']) {
            $text_offset .= 'margin-right:'.$offset.'px;';
        } elseif ('left' === $this->posts->args['image']['align']) {
            $text_offset .= 'margin-left:'.$offset.'px;';
        }

        $text_offset .= '" ';

        return $text_offset;
    }

    private function postClass(Post $post, int $count): string
    {
        if (($count % 2) === 0) {
            $odd_even_class = 'even';
        } else {
            $odd_even_class = 'odd';
        }

        $class = \get_post_class(['post-wrap', "post-count-{$count}", $odd_even_class], $post->get()->ID);

        $class[] = ($this->posts->args['image']['size']
            && !$post->hasThumbnail())
            ? 'no-post-thumb' : '';

        return \join(' ', $class);
    }

    private function post(int $id = 0): Post
    {
        return new Post($id);
    }

    /**
     * @param mixed[string]
     */
    private function WPQuery(array $args): WP_Query
    {
        return new WP_Query($args);
    }
}
