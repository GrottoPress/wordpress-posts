<?php

/**
 * Posts Pagination
 *
 * @package GrottoPress\WordPress\Posts
 * @since 0.1.0
 *
 * @author GrottoPress <info@grottopress.com>
 * @author N Atta Kusi Adusei
 */

declare (strict_types = 1);

namespace GrottoPress\WordPress\Posts;

use GrottoPress\Getter\Getter;
use WP_Query;

/**
 * Posts Pagination
 *
 * @since 0.1.0
 */
class Pagination
{
    use Getter;

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
     * Key
     *
     * @since 0.1.0
     * @access protected
     *
     * @var string $key Query arg whose value would be used for pagination.
     */
    protected $key;

    /**
     * Current Page
     *
     * @since 0.1.0
     * @access protected
     *
     * @var int $currentPage Current page number.
     */
    protected $currentPage;

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

        $this->setKey();
        $this->setCurrentPage();
    }

    /**
     * Get key
     *
     * @since 0.3.3
     * @access protected
     *
     * @return string
     */
    protected function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get current page
     *
     * @since 0.1.0
     * @access protected
     *
     * @return int current page number.
     */
    protected function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Render pagination
     *
     * @since 0.1.0
     * @access public
     *
     * @return string Pagination links.
     */
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
        '" itemprop="pagination">';
            $out .= $links;
        $out .= '</nav><!-- .pagination -->';
        
        return $out;
    }

    /**
     * Are we using the builtin pagination?
     *
     * @since 0.1.0
     * @access public
     *
     * @return bool
     */
    public function isBuiltIn(): bool
    {
        return ($this->key === 'paged');
    }

    /**
     * Set key
     *
     * @since 0.1.0
     * @access private
     */
    private function setKey()
    {
        if (($key = $this->posts->args['pagination']['key'])) {
            $this->key = \sanitize_key($key);
        } elseif (($id = $this->posts->args['id'])) {
            $this->key = \sanitize_key($id.'-pag');
        } else {
            $this->key = 'pag';
        }
    }

    /**
     * Set current page number
     *
     * @since 0.1.0
     * @access private
     */
    private function setCurrentPage()
    {
        if ($this->isBuiltIn()) {
            $this->currentPage = $this->currentPageBuiltin();
        } elseif (!empty($_GET[$this->key])) {
            $this->currentPage = \absint($_GET[$this->key]);
        }
        
        $this->currentPage = $this->currentPage ? $this->currentPage : 1;
    }

    /**
     * Get builtin current page number
     *
     * @since 0.1.0
     * @access private
     *
     * @return int Current page number.
     */
    private function currentPageBuiltin(): int
    {
        if (($page = \get_query_var('paged'))) {
            return \absint($page);
        }
        
        return 1;
    }

    /**
     * Pagination args
     *
     * @since 0.1.0
     * @access private
     *
     * @return array
     */
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
            $args['format'] = '?'.$this->key.'=%#%';
        }

        $args['prev_next'] = ($this->posts->args['pagination']['prev_text']
            && $this->posts->args['pagination']['next_text']);

        $args['add_fragment'] = ($this->posts->args['id']
            ? '#'.$this->posts->args['id'] : '');

        return $args;
    }
}
