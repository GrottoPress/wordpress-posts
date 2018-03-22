<?php
declare (strict_types = 1);

namespace GrottoPress\WordPress\Post;

class Time
{
    /**
     * @var Post
     */
    private $post;

    /**
     * @var string $context 'published' or 'updated'
     */
    protected $context;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * @param string $context 'published' or 'updated'
     */
    public function __construct(Post $post, string $context = '')
    {
        $this->post = $post;

        $this->context = \in_array($context, ['published', 'updated'])
            ? \sanitize_key($context) : 'published';

        $this->timestamp = ('updated' === $this->context
            ? \strtotime($this->post->get()()->post_modified)
            : \strtotime($this->post->get()()->post_date));
    }

    /**
     * Period since publishing or updating a post.
     *
     * Get (in string form, ready for output) the period since a given post
     * was either updated or published.
     *
     * @param string $format 'actual' (eg: Today 8:00), or 'difference'
     *              (eg: 2 mins ago), or 'mixed'.
     * @param string $before Text string to prepend to time.
     * @param string $after Text string to append to time.
     */
    public function render(
        string $format = 'actual',
        string $before = '',
        string $after = ''
    ): string {
        $format_allowed = ['actual', 'difference', 'mixed'];
        $format = \in_array($format, $format_allowed) ? $format : 'actual';

        $method = "render_{$format}";

        if (!\is_callable([$this, $method])) {
            return '';
        }

        $time_ago = '';

        if ($before) {
            $time_ago .= '<span class="before">'.$before.'</span> ';
        }

        $time_ago .= $this->$method();

        if ($after) {
            $time_ago .= ' <span class="after">'.$after.'</span>';
        }

        return $time_ago;
    }

    /**
     * Called if $format === 'actual'
     */
    private function render_actual(): string
    {
        if ($this->hoursSince() < 24) {
            if (\date('D', $this->timestamp) === \date(
                'D',
                \current_time('timestamp')
            )) { // If same day
                return \sprintf(
                    \esc_html__('Today %s'),
                    \date(\get_option('time_format'), $this->timestamp)
                );
            }

            return \sprintf(
                \esc_html__('Yesterday %s'),
                \date(\get_option('time_format'), $this->timestamp)
            );
        }

        if ($this->daysSince() < 7) {
            return \date('l', $this->timestamp).' '.
                \date(\get_option('time_format'), $this->timestamp);
        }

        return date(\get_option('date_format'), $this->timestamp).
            ' ('.\date(\get_option('time_format'), $this->timestamp).')';
    }

    /**
     * Called if $format === 'difference'
     */
    private function render_difference(): string
    {
        if (($period = $this->secondsSince()) < 60) {
            return \esc_html__('Few seconds ago');
        }

        if (($period = $this->minutesSince()) < 60) {
            return \sprintf(
                \esc_html(\_n('1 minute ago', '%s minutes ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->hoursSince()) < 24) {
            return \sprintf(
                \esc_html(\_n('1 hour ago', '%s hours ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->daysSince()) < 7) {
            return \sprintf(
                \esc_html(\_n('1 day ago', '%s days ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->weeksSince()) < 4) {
            return \sprintf(
                \esc_html(\_n('1 week ago', '%s weeks ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->monthsSince()) < 12) {
            return \sprintf(
                \esc_html(\_n('1 month ago', '%s months ago', $period)),
                \number_format_i18n($period)
            );
        }

        $period = $this->yearsSince();
        return sprintf(
            \esc_html(\_n('1 year ago', '%s years ago', $period)),
            \number_format_i18n($period)
        );
    }

    /**
     * Called if $format === 'mixed'
     */
    private function render_mixed(): string
    {
        if (($period = $this->secondsSince()) < 60) {
            return \esc_html__('Few seconds ago');
        }

        if (($period = $this->minutesSince()) < 60) {
            return \sprintf(
                \esc_html(\_n('1 minute ago', '%s minutes ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->hoursSince()) < 24) {
            return \sprintf(
                \esc_html(\_n('1 hour ago', '%s hours ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->daysSince()) < 7) {
            return \sprintf(
                \esc_html(\_n('1 day ago', '%s days ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->weeksSince()) < 4) {
            return \sprintf(
                \esc_html(\_n('1 week ago', '%s weeks ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->monthsSince()) < 4) {
            return \sprintf(
                \esc_html(\_n('1 month ago', '%s months ago', $period)),
                \number_format_i18n($period)
            );
        }

        if (($period = $this->monthsSince()) < 12) {
            return \date(\get_option('date_format'), $this->timestamp).
                ' ('.\date(\get_option('time_format'), $this->timestamp).')';
        }

        return \date(\get_option('date_format'), $this->timestamp).
            ' ('.\date(\get_option('time_format'), $this->timestamp).')';
    }

    /**
     * Period (in seconds) since publishing or updating a post.
     */
    private function secondsSince(): int
    {
        return \current_time('timestamp') - $this->timestamp;
    }

    /**
     * Period (in minutes) since publishing or updating a post.
     */
    private function minutesSince(): int
    {
        return \absint($this->secondsSince() / MINUTE_IN_SECONDS);
    }

    /**
     * Period (in hours) since publishing or updating a post.
     */
    private function hoursSince(): int
    {
        return \absint($this->secondsSince() / HOUR_IN_SECONDS);
    }

    /**
     * Period (in days) since publishing or updating a post.
     */
    private function daysSince(): int
    {
        return \absint($this->secondsSince() / DAY_IN_SECONDS);
    }

    /**
     * Period (in weeks) since publishing or updating a post.
     */
    private function weeksSince(): int
    {
        return \absint($this->secondsSince() / WEEK_IN_SECONDS);
    }

    /**
     * Period (in months) since publishing or updating a post.
     */
    private function monthsSince(): int
    {
        return \absint($this->secondsSince() / (DAY_IN_SECONDS * (365 / 12)));
    }

    /**
     * Period (in years) since publishing or updating a post.
     */
    private function yearsSince(): int
    {
        return \absint($this->secondsSince() / YEAR_IN_SECONDS);
    }
}
