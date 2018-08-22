<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

use GrottoPress\WordPress\Post;
use GrottoPress\WordPress\Posts\AbstractTestCase;
use GrottoPress\Mobile\Detector;
use WP_Post;
use Codeception\Util\Stub;
use tad\FunctionMocker\FunctionMocker;

class InfoTest extends AbstractTestCase
{
    public function _before()
    {
        FunctionMocker::replace(
            ['esc_attr', 'sanitize_key'],
            function (string $text): string {
                return $text;
            }
        );

        FunctionMocker::replace(
            'apply_filters',
            function (string $hook, $output): string {
                if ('filter_hook' === $hook) {
                    return 'Filter hook applied';
                }

                return $output;
            }
        );
    }

    public function testList()
    {
        FunctionMocker::replace('get_the_date', '10-10-10');
        FunctionMocker::replace('get_option');

        $get = $this->getMockBuilder('WP_Post')->getMock();
        $get->ID = 7;

        $info = new Info(Stub::makeEmpty(Post::class, [
            'thumbnail' => 'avatar',
            'author' => Stub::makeEmpty(Author::class, [
                'supported' => true,
            ]),
            'get' => $get,
        ]), Stub::makeEmpty(Detector::class), [
            'types' => ['avatar__40', 'filter_hook', 'published_date'],
            'separator' => '--',
        ]);

        $sep = '<span class="meta-sep">--</span>';
        $date = '<time class="published entry-date" itemprop="datePublished" datetime="10-10-10">10-10-10</time>';

        $this->assertSame(
            "avatar {$sep} Filter hook applied {$sep} {$date}",
            $info->list()
        );
    }
}
