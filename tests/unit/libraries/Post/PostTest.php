<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use GrottoPress\WordPress\Post;
use GrottoPress\WordPress\Posts\AbstractTestCase;
use WP_Post;
use WP_Post_Type;
use Codeception\Util\Stub;
use tad\FunctionMocker\FunctionMocker;

class PostTest extends AbstractTestCase
{
    public function _before()
    {
        FunctionMocker::replace(
            ['sanitize_key', 'sanitize_text_field', 'sanitize_title'],
            function (string $text): string {
                return $text;
            }
        );
    }

    public function testGet()
    {
        FunctionMocker::replace('get_post', function (int $id): WP_Post {
            $object = $this->getMockBuilder('WP_Post')->getMock();
            $object->ID = $id;

            return $object;
        });

        $post = new Post(44);

        $this->assertSame(44, $post->get()->ID);
    }

    public function testType()
    {
        FunctionMocker::replace(
            'get_post_type_object',
            function (string $type): WP_Post_Type {
                $object = $this->getMockBuilder('WP_Post_Type')->getMock();
                $object->name = $type;

                return $object;
            }
        );

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->post_type = 'tutorial';

        $post = Stub::makeEmptyExcept(Post::class, 'type', ['get' => $get]);

        $this->assertSame('tutorial', $post->type()->name);
    }

    /**
     * @dataProvider typeSupportsProvider
     */
    public function testTypeSupports(
        string $post_type,
        string $feature,
        bool $expected
    ) {
        $post_types = [
            'book' => [
                'supports' => ['thumbnails', 'comments'],
            ],
        ];

        FunctionMocker::replace(
            'post_type_supports',
            function (string $type, string $f) use ($post_types): bool {
                return (
                    isset($post_types[$type]['supports']) &&
                    \in_array($f, $post_types[$type]['supports'])
                );
            }
        );

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->post_type = $post_type;

        $post = Stub::makeEmptyExcept(
            Post::class,
            'typeSupports',
            ['get' => $get]
        );

        $this->assertSame($expected, $post->typeSupports($feature));
    }

    /**
     * @dataProvider hasThumbnailProvider
     */
    public function testHasThumbnail(bool $has_thumbnail, bool $expected)
    {
        FunctionMocker::replace(
            'has_post_thumbnail',
            function (WP_Post $post): bool {
                return $post->has_thumbnail;
            }
        );

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->has_thumbnail = $has_thumbnail;

        $post = Stub::makeEmptyExcept(
            Post::class,
            'hasThumbnail',
            ['get' => $get]
        );

        $this->assertSame($expected, $post->hasThumbnail());
    }

    public function testMeta()
    {
        FunctionMocker::replace(
            'get_post_meta',
            function (int $id, string $key, bool $single): array {
                return [$id, $key, $single];
            }
        );

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->ID = 3;

        $post = Stub::makeEmptyExcept(Post::class, 'meta', ['get' => $get]);

        $this->assertSame([3, 'meta_key', true], $post->meta('meta_key', true));
    }

    public function testTaxonomies()
    {
        $taxonomies = [
            'category' => [
                'name' => 'category',
                'hierarchical' => true,
            ],
            'post_tag' => [
                'name' => 'post_tag',
                'hierarchical' => false,
            ],
        ];

        $categories = [
            new class {
                public $term_id = 5;
                public $name = 'Politics';
                public $slug = 'politics';
                public $parent = 3;
                public $taxonomy = 'category';
            },
            new class {
                public $term_id = 3;
                public $name = 'News';
                public $slug = 'news';
                public $parent = 0;
                public $taxonomy = 'category';
            },
        ];

        $tags = [
            new class {
                public $term_id = 6;
                public $name = 'John Rawlings';
                public $slug = 'john-rawlings';
                public $taxonomy = 'post_tag';
            },
            new class {
                public $term_id = 4;
                public $name = 'John Kuffour';
                public $slug = 'john-kuffour';
                public $taxonomy = 'post_tag';
            },
        ];

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->ID = 4;
        $get->post_type = 'post';
        $get->post_parent = 0;
        $get->category = $categories;
        $get->post_tag = $tags;

        FunctionMocker::replace(
            'is_taxonomy_hierarchical',
            function (string $tax) use ($taxonomies): bool {
                return !empty($taxonomies[$tax]['hierarchical']);
            }
        );

        FunctionMocker::replace(
            'get_object_taxonomies',
            \json_decode(\json_encode($taxonomies))
        );

        FunctionMocker::replace(
            'get_the_terms',
            function (WP_Post $object, string $tax): array {
                return $object->{$tax};
            }
        );

        FunctionMocker::replace('is_wp_error', false);

        $post = Stub::makeEmptyExcept(
            Post::class,
            'taxonomies',
            ['get' => $get]
        );

        $this->assertSame(
            ['category' => [5 => $categories[0], 3 => $categories[1]]],
            $post->taxonomies('hierarchical')
        );

        $this->assertSame(
            ['post_tag' => [6 => $tags[0], 4 => $tags[1]]],
            $post->taxonomies('non_hierarchical')
        );

        $this->assertSame(
            [
                'category' => [5 => $categories[0], 3 => $categories[1]],
                'post_tag' => [6 => $tags[0], 4 => $tags[1]],
            ],
            $post->taxonomies()
        );
    }

    public function typeSupportsProvider()
    {
        return [
            'feature supported' => ['book', 'comments', true],
            'feature not supported' => ['book', 'editor', false],
        ];
    }

    public function hasThumbnailProvider()
    {
        return [
            'has thumbnail' => [true, true],
            'has no thumbnail' => [false, false],
        ];
    }
}
