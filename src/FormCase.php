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

use WP_User;
use WP_Post;

/**
 * Handle plugin workflow when form markup is required via ajax.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package RejectNotify
 */
class FormCase implements CaseInterface
{
    const NONCE = 'reject-notify-check';

    /**
     * @var \GM\RejectNotify\MetaHandler
     */
    private $meta;

    /**
     * @var \WP_Post
     */
    private $post;

    /**
     * @var \WP_User
     */
    private $author;

    /**
     * @var string
     */
    private $path;

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
        $postId = filter_input(INPUT_GET, 'postid', FILTER_SANITIZE_NUMBER_INT);
        $post = $postId ? get_post($postId) : false;
        $allowed = apply_filters(Launcher::SLUG.'_post_types', ['post']);
        if (
            ! $post instanceof WP_Post
            || $this->meta->has($post->ID, $post->post_author)
            || ! in_array($post->post_type, $allowed, true)
        ) {
            return false;
        }
        $this->post = $post;
        $this->author = new WP_User($post->post_author);
        $this->path = $path;
        $mail = $this->author && $this->author->exists() ? $this->author->get('user_email') : false;

        return $mail && filter_var($mail, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Print form HTML markup end exit ajax request.
     */
    public function run()
    {
        add_action('wp_ajax_'.Launcher::SLUG.'_show_form', function () {
            die($this->printForm());
        });
    }

    /**
     * Return form HTML replacing variables in a template string.
     *
     * @return string
     */
    private function printForm()
    {
        $title = apply_filters('the_title', $this->post->post_title);
        $nonce = self::NONCE.get_current_blog_id();
        $data = [
            'action'       => esc_attr(Launcher::SLUG.'_send_mail'),
            'postId'       => $this->post->ID,
            'postTitle'    => esc_attr($title),
            'recipient'    => esc_attr($this->author->get('user_email')),
            'messageLabel' => esc_html__('Message:', 'reject-notify'),
            'button'       => get_submit_button(esc_html__('Send', 'reject-notify')),
            'nonce'        => wp_nonce_field($nonce, self::NONCE, true, false),
            'title'        => sprintf(
                esc_html__('Send reject mail to %s', 'reject-notify'),
                esc_html($this->author->get('display_name'))
            ),
            'message'      => vsprintf(
                esc_html__('Sorry %s, your post %s was rejected.', 'reject-notify'),
                [esc_html($this->author->get('display_name')), '&quot;'.esc_html($title).'&quot;']
            ),

        ];
        $template = file_get_contents(dirname($this->path).'/templates/form.php');
        foreach ($data as $key => $value) {
            $template = str_replace("{{{{$key}}}}", $value, $template);
        }

        return $template;
    }
}
