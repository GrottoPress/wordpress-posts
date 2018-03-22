<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use Codeception\Util\Stub;
use tad\FunctionMocker\FunctionMocker;
use GrottoPress\WordPress\Posts\AbstractTestCase;

class AuthorTest extends AbstractTestCase
{
    /**
     * @dataProvider postsURLProvider
     */
    public function testPostsURL(int $author_id, string $expected)
    {
        FunctionMocker::replace(
            'get_author_posts_url',
            function (int $id): string {
                if (-1 === $id) {
                    return '#';
                }

                if (0 === $id) {
                    return '';
                }

                return "http://my.site/author/{$id}/";
            }
        );

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->post_author = $author_id;

        $author = new Author(Stub::makeEmpty(Post::class, ['get' => $get]));

        $this->assertSame($expected, $author->postsURL());
    }

    public function testMeta()
    {
        FunctionMocker::replace(
            'get_the_author_meta',
            function (string $key, int $id): array {
                return [$key, $id];
            }
        );

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->post_author = 9;

        $author = new Author(Stub::makeEmpty(Post::class, ['get' => $get]));

        $this->assertSame(['display_name', 9], $author->meta('display_name'));
    }

    public function postsURLProvider()
    {
        return [
            'author posts url is empty' => [0, ''],
            'author posts url is "#"' => [-1, ''],
            'author posts url not empty' => [5, 'http://my.site/author/5/'],
        ];
    }
}
