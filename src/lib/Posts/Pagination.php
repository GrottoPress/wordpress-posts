<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Posts;

use GrottoPress\WordPress\Posts;
use GrottoPress\Getter\GetterTrait;
use WP_Query;

class Pagination
{
    use GetterTrait;

    /**
     * @var Posts
     */
    protected $posts;

    /**
     * @var string $key Query arg whose value would be used for pagination.
     */
    protected $key;

    /**
     * @var int
     */
    protected $currentPage;

    public function __construct(Posts $posts)
    {
        $this->posts = $posts;

        $this->setKey();
        $this->setCurrentPage();
    }

    protected function getKey(): string
    {
        return $this->key;
    }

    protected function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function render(string $position, WP_Query $query): string
    {
        $out = '';

        if (!\in_array(
            $position,
            $this->posts->args['pagination']['position']
        )) {
            return $out;
        }

        if ($query->max_num_pages < 2) {
            return $out;
        }

        if (!($links = \paginate_links($this->args($query)))) {
            return $out;
        }

        $out .= '<nav class="pagination '.$position.
        '">';
            $out .= $links;
        $out .= '</nav><!-- .pagination -->';

        return $out;
    }

    public function isBuiltIn(): bool
    {
        return ($this->key === 'paged');
    }

    private function setKey()
    {
        if ($key = $this->posts->args['pagination']['key']) {
            $this->key = \sanitize_key($key);
        } else {
            $this->key = "page-{$this->posts->id}";
        }
    }

    private function setCurrentPage()
    {
        if ($this->isBuiltIn()) {
            $this->currentPage = $this->currentPageBuiltin();
        } elseif (!empty($_GET[$this->key])) {
            $this->currentPage = \absint($_GET[$this->key]);
        }

        $this->currentPage = $this->currentPage ?: 1;
    }

    /**
     * Get builtin current page number
     */
    private function currentPageBuiltin(): int
    {
        if ($page = \get_query_var('paged')) {
            return \absint($page);
        }

        return 1;
    }

    private function args(WP_Query $query): array
    {
        $args = [];

        $args['total'] = $query->max_num_pages;
        $args['current'] = $this->currentPage;
        $args['mid_size'] = $this->posts->args['pagination']['mid_size'];
        $args['end_size'] = $this->posts->args['pagination']['end_size'];
        $args['prev_text'] = $this->posts->args['pagination']['prev_text'];
        $args['next_text'] = $this->posts->args['pagination']['next_text'];

        if (!$this->isBuiltIn()) {
            $args['format'] = "?{$this->key}=%#%";
        }

        $args['prev_next'] = ($this->posts->args['pagination']['prev_text']
            && $this->posts->args['pagination']['next_text']);

        $args['add_fragment'] = "#{$this->posts->id}";

        return $args;
    }
}
