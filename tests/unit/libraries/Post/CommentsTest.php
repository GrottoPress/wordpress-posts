<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use Codeception\Util\Stub;
use tad\FunctionMocker\FunctionMocker;
use GrottoPress\WordPress\Posts\AbstractTestCase;
use WP_Post;

class CommentsTest extends AbstractTestCase
{
    public function testCount()
    {
        FunctionMocker::replace(
            'get_comments_number',
            function (WP_Post $get): int {
                return $get->comments_number;
            }
        );

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->comments_number = 7;

        $comments = new Comments(Stub::makeEmpty(Post::class, [
            'get' => $get,
        ]));

        $this->assertSame(7, $comments->count());
    }
}
