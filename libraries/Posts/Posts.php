<?php

/**
 * WordPress Posts
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
use GrottoPress\Getter\Getter;

/**
 * WordPress Posts
 *
 * @since 0.1.0
 */
final class Posts
{
    use Getter;
    
    /**
     * Arguments
     *
     * @since 0.1.0
     * @access private
     *
     * @var array $args Arguments passed via constructor.
     */
    private $args;

    /**
     * Loop
     *
     * @since 0.1.0
     * @access private
     *
     * @var Loop $loop Loop.
     */
    private $loop;

    /**
     * Pagination
     *
     * @since 0.1.0
     * @access private
     *
     * @var Pagination $pagination Pagination.
     */
    private $pagination;
    
    /**
     * Constructor
     *
     * @param array $args Arguments.
     *
     * @since 0.1.0
     * @access public
     */
    public function __construct(array $args = [])
    {
        $this->setArgs($args);
        
        $this->pagination = new Pagination($this);
        $this->loop = new Loop($this);
    }

    /**
     * Get args
     *
     * @since 0.1.0
     * @access private
     *
     * @return array Args.
     */
    private function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Get pagination
     *
     * @since 0.1.0
     * @access private
     *
     * @return Pagination
     */
    private function getPagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * Do the query
     *
     * @since 0.1.0
     * @access public
     */
    public function render(): string
    {
        $this->defineDefaults();
        $this->applyImageAlign();
        $this->applyRelatedTo();
        $this->applyLayout();
        $this->sanitizeClassAttr();
        
        return $this->loop->run();
    }

    /**
     * Define default attributes
     *
     * @since 0.1.0
     * @access private
     */
    private function defineDefaults()
    {
        $this->contentDefaults();
        $this->postListDefaults();
        $this->titleTagDefaults();
        $this->relatedToDefaults();
        $this->paginationDefaults();
        $this->layoutDefaults();
    }

    /**
     * Apply image float
     *
     * @since 0.1.0
     * @access private
     */
    private function applyImageAlign()
    {
        $allowed = ['left', 'right', 'none'];
        
        if (\in_array(\sanitize_key($this->args['image']['align']), $allowed)) {
            $class = \sanitize_title(
                'img-align-'.$this->args['image']['align']
            );

            $this->args['class'] = \str_ireplace(
                $class,
                '',
                $this->args['class']
            );
            $this->args['class'] .= ' '.$class;
        }
    }

    /**
     * Apply related to
     *
     * @since 0.1.0
     * @access private
     */
    private function applyRelatedTo()
    {
        if (($related_to = \absint($this->args['related_to'])) < 1) {
            return;
        }

        $this->args['wp_query']['post__not_in'] = (array)$related_to;
        $this->args['wp_query']['post_type'] = \get_post_type($related_to);

        $taxonomies = $this->post($related_to)->taxonomies('non_hierarchical');

        if (!$taxonomies) {
            return;
        }

        foreach ($taxonomies as $slug => $terms) {
            $this->args['wp_query']['tax_query'][] = [
                'taxonomy' => $slug,
                'terms' => $terms,
                'operator' => 'IN',
                'field' => 'term_id',
            ];
        }

        $this->args['wp_query']['tax_query']['relation'] = 'OR';
    }

    /**
     * Apply layout
     *
     * @since 0.1.0
     * @access private
     */
    private function applyLayout()
    {
        if (!$this->args['layout']) {
            return;
        }

        $class = \sanitize_title('layout-'.$this->args['layout']);

        $this->args['class'] = \str_ireplace($class, '', $this->args['class']);
        $this->args['class'] .= ' '.$class;
    }
    
    /**
     * Content defaults
     *
     * @since 0.1.0
     * @access private
     */
    private function contentDefaults()
    {
        if (!$this->isContent()) {
            return;
        }
        
        $this->args['class'] = \str_ireplace(
            ['big', 'small', 'show-content'],
            [],
            $this->args['class']
        );

        $this->args['class'] .= ' big show-content';
        
        $this->args['image']['size'] = '';
        $this->args['image']['align'] = '';
        
        $this->args['text_offset'] = 0;
        $this->args['title']['position'] = 'top';
        $this->args['layout'] = 'stack';
    }
    
    /**
     * Post list defaults
     *
     * @since 0.1.0
     * @access private
     */
    private function postListDefaults()
    {
        if (!$this->isList()) {
            return;
        }
        
        $this->args['class'] = \str_ireplace(
            ['big', 'small', 'show-content'],
            [],
            $this->args['class']
        );

        $this->args['class'] .= ' small';
        
        $this->args['image']['size'] = '';
        $this->args['image']['align'] = '';
        
        $this->args['text_offset'] = '';
        $this->args['before_title']['info'] = [];
        $this->args['layout'] = 'stack';
        
        if ($this->isContent()) {
            $this->args['excerpt']['length'] = 0;
        }
    }

    /**
     * Related posts defaults
     *
     * @since 0.1.0
     * @access private
     */
    private function relatedToDefaults()
    {
        if (\absint($this->args['related_to']) < 1) {
            return;
        }

        $this->args['wp_query']['s'] = '';

        $this->args['wp_query']['category_name'] = '';
        $this->args['wp_query']['cat'] = '';
        $this->args['wp_query']['category__not_in'] = [];
        $this->args['wp_query']['category__in'] = [];
        $this->args['wp_query']['category__and'] = [];

        $this->args['wp_query']['name'] = '';
        $this->args['wp_query']['title'] = '';
        $this->args['wp_query']['p'] = '';
        $this->args['wp_query']['post__in'] = [];
        $this->args['wp_query']['post__not_in'] = [];
        $this->args['wp_query']['post_name__in'] = [];
        $this->args['wp_query']['post_type'] = '';
        $this->args['wp_query']['pagename'] = '';
        $this->args['wp_query']['page_id'] = '';
        $this->args['wp_query']['post_parent'] = '';
        $this->args['wp_query']['post_parent__in'] = [];
        $this->args['wp_query']['post_parent__not_in'] = [];

        $this->args['wp_query']['tag'] = '';
        $this->args['wp_query']['tag_id'] = '';
        $this->args['wp_query']['tag__in'] = [];
        $this->args['wp_query']['tag__not_in'] = [];
        $this->args['wp_query']['tag__and'] = [];
        $this->args['wp_query']['tag_slug__in'] = [];
        $this->args['wp_query']['tag_slug__and'] = [];

        $this->args['wp_query']['tax_query'] = [];
    }
    
    /**
     * Title tag defaults
     *
     * @since 0.1.0
     * @access private
     */
    private function titleTagDefaults()
    {
        $allowed = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        
        if (\in_array(\sanitize_key($this->args['title']['tag']), $allowed)) {
            return;
        }
        
        $this->args['title']['tag'] = 'h2';
    }

    /**
     * Pagination defaults
     *
     * @since 0.1.0
     * @access private
     */
    private function paginationDefaults()
    {
        if (!\in_array(
            ['top', 'bottom'],
            $this->args['pagination']['position']
        )) {
            $this->args['wp_query']['paged'] = $this->pagination->currentPage;
        } else {
            $this->args['wp_query']['paged'] = 1;
        }

        if (!isset($this->args['wp_query']['offset'])) {
            $this->args['wp_query']['offset'] = 0;
        }

        if (!isset($this->args['wp_query']['posts_per_page'])) {
            $this->args['wp_query']['posts_per_page'] = 1;
        }
        
        /**
         * Pagination WON'T work with offset if we simply use the $offset
         */
        $this->args['wp_query']['offset'] =
            \absint($this->args['wp_query']['offset']) + (
                ($this->pagination->currentPage - 1)
                * \absint($this->args['wp_query']['posts_per_page'])
           );
    }

    /**
     * Layout defaults
     *
     * @since 0.1.0
     * @access private
     */
    private function layoutDefaults()
    {
        $allowed = ['stack', 'grid'];
        
        if (\in_array(\sanitize_key($this->args['layout']), $allowed)) {
            return;
        }
        
        $this->args['layout'] = 'stack';
    }

    /**
     * Are we displaying a list of posts? (ie `ul`)
     *
     * @since 0.1.0
     * @access public
     *
     * @return bool
     */
    public function isList(): bool
    {
        return \in_array(\sanitize_key($this->args['tag']), ['ol', 'ul']);
    }

    /**
     * Is content
     *
     * @since 0.1.0
     * @access public
     *
     * @return bool Whether or not we are showing full post content
     */
    public function isContent(): bool
    {
        return ($this->args['excerpt']['length'] < -1);
    }

    /**
     * Arguments
     *
     * @since 0.1.0
     * @access private
     *
     * @return array
     */
    private function setArgs(array $args)
    {
        /**
         * @filter grotto_wp_posts_args
         *
         * @var string $args Args.
         *
         * @since 0.1.0
         */
        $args = \apply_filters('grotto_wp_posts_args', $args);

        $this->args = \array_replace_recursive($this->defaultArgs(), $args);
    }

    /**
     * Default arguments
     *
     * @since 0.1.0
     * @access private
     *
     * @return array Default args.
     */
    private function defaultArgs(): array
    {
        return [
            'id' => '',
            'class' => 'small',
            'tag' => 'div',
            'layout' => 'stack',
            'text_offset' => 0,
            'related_to' => 0,
            'image' => [
                'size' => '',
                'align' => '',
                'margin' => '',
                'link' => true,
            ],
            'excerpt' => [
                'length' => 0,
                'paginate' => true,
                'more_text' => \esc_html__('read more'),
                'after' => [
                    'types' => [],
                    'separator' => '|',
                    'before' => '<aside class="entry-meta after-content">',
                    'after' => '</aside>',
                ],
            ],
            'title' => [
                'tag' => 'h2',
                'position' => '',
                'length' => -1,
                'link' => true,
                'before' => [
                    'types' => [],
                    'separator' => '|',
                    'before' => '<aside class="entry-meta before-title">',
                    'after' => '</aside>',
                ],
                'after' => [
                    'types' => [],
                    'separator' => '|',
                    'before' => '<aside class="entry-meta after-title">',
                    'after' => '</aside>',
                ],
            ],
            'pagination' => [
                'position' => [],
                'key' => '',
                'mid_size' => null,
                'end_size' => null,
                'prev_text' => \esc_html__('&larr; Previous'),
                'next_text' => \esc_html__('Next &rarr;'),
            ],
            'wp_query' => [],
        ];
    }

    /**
     * Sanitize 'class' arg
     *
     * @since 0.1.0
     * @access private
     */
    private function sanitizeClassAttr()
    {
        $this->args['class'] = \str_replace(',', ' ', $this->args['class']);
        $this->args['class'] = \preg_replace(
            '/\s\s+/',
            ' ',
            $this->args['class']
        );
        $this->args['class'] = \sanitize_text_field($this->args['class']);
    }

    /**
     * Get post
     *
     * @param int $id Post ID.
     *
     * @since 0.1.0
     * @access public
     *
     * @return Post
     */
    public function post(int $id = 0): Post
    {
        return new Post($id);
    }
}
