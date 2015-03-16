<?php
/*
 * This file is part of the Reject & Notify package.
 *
 * (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GM\RejectNotify;

/**
 * Handle plugin workflow when current screen is post list for one of the supported post types.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package RejectNotify
 */
class ListCase implements CaseInterface
{
    /**
     * @var \GM\RejectNotify\MetaHandler
     */
    private $meta;

    /**
     * @var bool
     */
    private $allowed = true;

    /**
     * @param \GM\RejectNotify\MetaHandler $meta
     */
    public function __construct(MetaHandler $meta)
    {
        $this->meta = $meta;
    }

    /**
     * @inheritdoc
     */
    public function init($path)
    {
        if (apply_filters(Launcher::SLUG.'_admin_column_pending_only', false)) {
            add_action('wp', function () {
                /** @var \WP_Query $wp_query */
                global $wp_query;
                $this->allowed = in_array('pending', (array) $wp_query->get('post_status'), true);
            });
        }

        return true;
    }

    /**
     * Add hooks that allow to print reject information on a column in post list table.
     */
    public function run()
    {
        if (! $this->allowed) {
            return;
        }
        $type = get_current_screen()->id === 'edit-page' ? 'pages' : 'posts';
        add_filter("manage_{$type}_columns", function ($columns) {
            return $this->columnHeader($columns);
        });
        add_action("manage_{$type}_custom_column", function ($column, $pid) {
            $this->columnContent($column, $pid);
        }, 10, 2);
    }

    /**
     * Add column header to post list table.
     *
     * @param  array $columns
     * @return array
     */
    private function columnHeader(array $columns)
    {
        if (is_array($columns)) {
            $columns[Launcher::SLUG.'_status'] = __('Rejected Status', 'reject-notify');
        }

        return $columns;
    }

    /**
     * Print the 'Rejected Status' column according to post meta.
     *
     * @param string $column
     * @param string $pid
     */
    private function columnContent($column, $pid)
    {
        if ($column !== Launcher::SLUG.'_status') {
            return;
        }
        $post = get_post($pid);
        $allowed = apply_filters(Launcher::SLUG.'_post_types', ['post']);
        if (!in_array($post->post_type, $allowed, true)) {
            return;
        }
        $has = $this->meta->has($pid);
        $status = $post->post_status;
        if (! in_array($status, ['pending', 'draft'], true)) {
            $has and $this->meta->delete($pid);
        }
        if ($status === 'pending') {
            $has
                ? esc_html_e('Rejected', 'reject-notify')
                : esc_html_e('Needs Approval', 'reject-notify');
        }
    }
}
