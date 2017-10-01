<?php

/**
 * Posts Pagination
 *
 * @package GrottoPress\WordPress\Posts
 * @since 0.1.0
 *
 * @author GrottoPress <info@grottopress.com>
 * @author N Atta Kus Adusei
 */

declare (strict_types = 1);

namespace GrottoPress\WordPress\Posts;

use WP_Query;

/**
 * Posts Pagination
 *
 * @since 0.1.0
 */
final class Pagination
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
     * Key
     *
     * @since 0.1.0
     * @access private
     *
     * @var string $key Query arg whose value would be used for pagination.
     */
    private $key;

    /**
     * Current Page
     *
     * @since 0.1.0
     * @access private
     *
     * @var int $current_page Current page number.
     */
    private $current_page;
    
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
     * Get current page
     *
     * @since 0.1.0
     * @access public
     *
     * @return int current page number.
     */
    public function currentPage(): int
    {
         return $this->current_page;
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
            $this->posts->args()['pagination']['position']
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
     * Set key
     *
     * @since 0.1.0
     * @access private
     */
    private function setKey()
    {
        if (($key = $this->posts->args()['pagination']['key'])) {
            $this->key = \sanitize_key($key);
        } elseif (($id = $this->posts->args()['id'])) {
            $this->key = \sanitize_key($id.'_pag');
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
            $this->current_page = $this->currentPageBuiltin();
        } elseif (!empty($_GET[$this->key])) {
            $this->current_page = \absint($_GET[$this->key]);
        }
        
        $this->current_page = $this->current_page ? $this->current_page : 1;
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
     * Are we using the builtin pagination?
     *
     * @since 0.1.0
     * @access private
     *
     * @return bool
     */
    private function isBuiltIn(): bool
    {
        global $wp_rewrite;

        return (
            $this->key === $wp_rewrite->pagination_base
            && $wp_rewrite->using_permalinks()
        );
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
        $args['current'] = $this->current_page;
        $args['mid_size'] = $this->posts->args()['pagination']['mid_size'];
        $args['end_size'] = $this->posts->args()['pagination']['end_size'];
        $args['prev_text'] = $this->posts->args()['pagination']['prev_text'];
        $args['next_text'] = $this->posts->args()['pagination']['next_text'];

        if (!$this->isBuiltIn()) {
            $args['format'] = '?'.$this->key.'=%#%';
        }

        $args['prev_next'] = ($this->posts->args()['pagination']['prev_text']
            && $this->posts->args()['pagination']['next_text']);

        $args['add_fragment'] = ($this->posts->args()['id']
            ? '#'.$this->posts->args()['id'] : '');

        return $args;
    }
}
