<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress;

use GrottoPress\Getter\GetterTrait;

class Posts
{
    use GetterTrait;

    /**
     * @var array $args Arguments passed via constructor.
     */
    protected $args;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var Posts\Loop
     */
    protected $loop;

    /**
     * @var Posts\Pagination
     */
    protected $pagination;

    public function __construct(array $args = [])
    {
        $this->setArgs($args);

        $this->pagination = new Posts\Pagination($this);
        $this->loop = new Posts\Loop($this);
    }

    protected function getArgs(): array
    {
        return $this->args;
    }

    protected function getId(): string
    {
        if (!$this->id) {
            $json = \wp_json_encode($this->getArgs());
            $this->id = \substr(\md5($json), 0, 10);
        }

        return $this->id;
    }

    protected function getPagination(): Posts\Pagination
    {
        return $this->pagination;
    }

    public function render(): string
    {
        $this->defineDefaults();
        $this->applyImageAlign();
        $this->applyRelatedTo();
        $this->applyLayout();
        $this->sanitizeClassAttr();

        return $this->loop->run();
    }

    private function defineDefaults()
    {
        $this->contentDefaults();
        $this->postListDefaults();
        $this->titleTagDefaults();
        $this->relatedToDefaults();
        $this->postsPerPageDefaults();
        $this->paginationDefaults();
        $this->layoutDefaults();
    }

    private function applyImageAlign()
    {
        $allowed = ['left', 'right', 'none'];

        if (\in_array(\sanitize_key($this->args['image']['align']), $allowed)) {
            $class = \sanitize_title(
                "img-align-{$this->args['image']['align']}"
            );

            $this->args['class'] = \str_ireplace(
                \array_map(function (string $align): string {
                    return "img-align-{$align}";
                }, $allowed),
                '',
                $this->args['class']
            );

            $this->args['class'] .= " {$class}";
        }
    }

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
                'terms' => \array_keys($terms),
                'operator' => 'IN',
                'field' => 'term_id',
            ];
        }

        $this->args['wp_query']['tax_query']['relation'] = 'OR';
    }

    private function applyLayout()
    {
        if (!$this->args['layout']) {
            return;
        }

        $class = \sanitize_title("layout-{$this->args['layout']}");

        $this->args['class'] = \str_ireplace(
            \array_map(function (string $layout): string {
                return "layout-{$layout}";
            }, $this->allowedLayouts()),
            '',
            $this->args['class']
        );

        $this->args['class'] .= " {$class}";
    }

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

    private function titleTagDefaults()
    {
        $allowed = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

        if (\in_array(\sanitize_key($this->args['title']['tag']), $allowed)) {
            return;
        }

        $this->args['title']['tag'] = 'h2';
    }

    private function postsPerPageDefaults()
    {
        if (!$this->pagination->isBuiltIn()) {
            return;
        }

        $this->args['wp_query']['posts_per_page'] = \get_option(
            'posts_per_page'
        );
    }

    private function paginationDefaults()
    {
        if (\in_array(
            ['top', 'bottom'],
            $this->args['pagination']['position']
        )) {
            $this->args['wp_query']['paged'] = 1;
        } else {
            $this->args['wp_query']['paged'] = $this->pagination->currentPage;
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

    private function layoutDefaults()
    {
        if (\in_array(
            \sanitize_key($this->args['layout']),
            $this->allowedLayouts()
        )) {
            return;
        }

        $this->args['layout'] = '';
    }

    /**
     * @return string[int]
     */
    private function allowedLayouts(): array
    {
        return ['stack', 'grid'];
    }

    /**
     * Are we displaying a list of posts? (ie `ul`)
     */
    public function isList(): bool
    {
        return \in_array(\sanitize_key($this->args['tag']), ['ol', 'ul']);
    }

    /**
     * Are we showing full post content?
     */
    public function isContent(): bool
    {
        return ($this->args['excerpt']['length'] < -1);
    }

    private function setArgs(array $args)
    {
        /**
         * @filter grotto_wp_posts_args
         * @var array $args
         */
        $args = \apply_filters('grotto_wp_posts_args', $args);

        $this->args = \array_replace_recursive($this->defaultArgs(), $args);
    }

    private function defaultArgs(): array
    {
        return [
            'class' => 'small',
            'tag' => 'div',
            'layout' => '',
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
                'more_text' => \esc_html__('read more', 'grotto-wp-posts'),
                'after' => [
                    'types' => [],
                    'separator' => '|',
                    'before' => '<small class="entry-meta after-content">',
                    'after' => '</small>',
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
                    'before' => '<small class="entry-meta before-title">',
                    'after' => '</small>',
                ],
                'after' => [
                    'types' => [],
                    'separator' => '|',
                    'before' => '<small class="entry-meta after-title">',
                    'after' => '</small>',
                ],
            ],
            'pagination' => [
                'position' => [],
                'key' => '',
                'mid_size' => 2,
                'end_size' => 1,
                'prev_text' => \esc_html__('&larr; Previous', 'grotto-wp-posts'),
                'next_text' => \esc_html__('Next &rarr;', 'grotto-wp-posts'),
            ],
            'wp_query' => [],
        ];
    }

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

    private function post(int $id = 0): Post
    {
        return new Post($id);
    }
}
