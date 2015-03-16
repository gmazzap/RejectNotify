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

use WP_Post;

/**
 * Handle plugin workflow when in post edit admin screen.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package RejectNotify
 */
class PostCase implements CaseInterface
{
    /**
     * @var \GM\RejectNotify\MetaHandler
     */
    private $meta;

    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $inited = false;

    /**
     * @var bool
     */
    private $hasMeta;

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
        $postId = filter_input(INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT);
        $post = $postId ? get_post($postId) : false;
        if (! $post instanceof WP_Post) {
            return false;
        }
        $this->hasMeta = $this->meta->has($post->ID, $post->post_author);
        $this->path = $path;
        if (! in_array($post->post_status, ['pending', 'draft'], true) && $this->hasMeta) {
            $this->meta->delete($post->ID);
        } elseif (
            $post->post_status === 'pending'
            && (int) $post->post_author !== (int) get_current_user_id()
        ) {
            /** @var \stdClass $postType */
            /** @var \stdClass $caps */
            $postType = get_post_type_object($post->post_type);
            $caps = $postType->cap;

            $this->inited = ! user_can($post->post_author, $caps->publish_posts);
        }

        return $this->inited;
    }

    /**
     * Adds to post edit screen a button that when clicked open a form that allows to send a message
     * to the author of a pending post that have been rejected.
     *
     * @use \GM\RejectNotify\PostCase::button()
     * @use \GM\RejectNotify\PostCase::assets()
     */
    public function run()
    {
        $callback = function () {
          if ($this->inited) {
              $method = current_filter() === 'admin_enqueue_scripts' ? 'assets' : 'button';

              return call_user_func_array([$this, $method], func_get_args());
          }
        };
        add_action('admin_enqueue_scripts', $callback);
        add_action('post_submitbox_misc_actions', $callback);
    }

    /**
     * Enqueue and localize the scripts
     */
    private function assets()
    {
        if ($this->hasMeta) {
            return;
        }
        $data = [
            'action'           => Launcher::SLUG.'_show_form',
            'please_wait'      => __('Please wait...', 'reject-notify'),
            'def_mail_error'   => __('Error on sending email.', 'reject-notify'),
            'debug'            => defined('WP_DEBUG') && WP_DEBUG ? '1' : '',
            'debug_info'       => __('Debug info', 'reject-notify'),
            'sender'           => __('Sender', 'reject-notify'),
            'recipient'        => __('Recipient', 'reject-notify'),
            'email_content'    => __('Email Content', 'reject-notify'),
            'email_subject'    => __('Email Subject', 'reject-notify'),
            'already_rejected' => __('Already rejected and notified.', 'reject-notify'),
            'ajax_wrong_data'  => __('Ajax callback returned no or wrong data.', 'reject-notify'),
            'ajax_failed'      => __('Ajax call failed.', 'reject-notify'),
        ];
        $rel = 'js/reject-notify';
        $rel .= defined('WP_DEBUG') && WP_DEBUG ? '.js' : '.min.js';
        $url = plugins_url($rel, $this->path);
        $path = dirname($this->path)."/{$rel}";
        $ver = @filemtime($path) ?: (defined('WP_DEBUG') && WP_DEBUG ? time() : null);
        wp_enqueue_style('thickbox');
        wp_enqueue_script(Launcher::SLUG, $url, ['jquery', 'thickbox'], $ver);
        wp_localize_script(Launcher::SLUG, 'RejectNotifyData', array_map('strip_tags', $data));
    }

    /**
     * Print the Reject button or the 'already notified' notice.
     */
    private function button()
    {
        $html = '<div class="misc-pub-section" style="text-align:right;">';
        if ($this->hasMeta) {
            $already = esc_html__('Already rejected and notified.', 'reject-notify');

            return printf($html.'<strong>%s</strong></div>', $already);
        }
        $label = esc_html__('Reject and Notify', 'reject-notify');
        $html .=
            '<input name="send_reject_mail_box" data-post="'
            .'%s'
            .'" class="button button-primary button-large" id="send_reject_mail_box" value="'
            .'%s'
            .'" type="button"></div>';
        printf($html, $GLOBALS['post']->ID, $label);
    }
}
