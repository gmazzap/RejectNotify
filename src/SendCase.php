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
 * Handle plugin workflow when a mail sending is required via ajax.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package RejectNotify
 */
class SendCase implements CaseInterface
{
    /**
     * @var \GM\RejectNotify\MetaHandler
     */
    private $meta;

    /**
     * @var int
     */
    private $authorID;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $error;

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
        $args = [
            'post_title'    => FILTER_SANITIZE_STRING,
            'reason'        => FILTER_SANITIZE_STRING,
            'postid'        => FILTER_SANITIZE_NUMBER_INT,
            'recipient'     => FILTER_VALIDATE_EMAIL,
            FormCase::NONCE => FILTER_SANITIZE_STRING,
        ];
        $this->data = filter_input_array(INPUT_POST, $args, true);
        $author = get_user_by('email', $this->data['recipient']);
        $this->authorID = $author instanceof WP_User ? $author->ID : 0;
        $this->validate();
        empty($this->error) or $this->error();

        return true;
    }

    /**
     * Sends email to post author with reject reason.
     */
    public function run()
    {
        $sender = wp_get_current_user()->get('user_email');
        $headers = 'From: '.$sender.'<'.$sender.'>'."\r\n";
        $subject = sprintf(
            esc_html__('Your post on %s was rejected.', 'reject-notify'),
            esc_html(get_bloginfo('name'))
        );
        add_filter('wp_mail_content_type', function () {
            return 'text/html';
        }, PHP_INT_MAX);
        $success = wp_mail($this->data['recipient'], $subject, $this->data['reason'], $headers);
        $format = $success
            ? esc_html__('Email sent to %s!', 'reject-notify')
            : esc_html__('Error on sending email to %s', 'reject-notify');
        $message = sprintf($format, $this->data['recipient']);
        $class = $success ? 'updated' : 'error';
        $json = array_merge($this->data, compact('message', 'class', 'sender', 'subject'));
        $success and $this->meta->add($this->data['postid'], $this->authorID);
        wp_send_json($json);
    }

    /**
     * Validate request data.
     */
    private function validate()
    {
        $errors = [
            esc_html__('Wrong data error.', 'reject-notify'),
            esc_html__('Missing data error.', 'reject-notify'),
            esc_html__('Error on validating nonce.', 'reject-notify'),
            esc_html__('Invalid recipient mail.', 'reject-notify'),
            esc_html__('Post author was already rejected and notified.', 'reject-notify'),
        ];
        $callbacks = [
            function ($error) {
               $allowed = apply_filters(Launcher::SLUG.'_post_types', ['post']);
               $post = $this->data['postid'] ? get_post($this->data['postid']) : false;

               return $post instanceof WP_Post && in_array($post->post_type, $allowed, true) ? '' : $error;
            },
            function ($error) {
                return array_filter($this->data) === $this->data ? '' : $error;
            },
            function ($error) {
                return
                    wp_verify_nonce(
                        $this->data[FormCase::NONCE],
                        FormCase::NONCE.get_current_blog_id()
                    ) ? '' : $error;
            },
            function ($error) {
                return filter_var($this->data['recipient'], FILTER_VALIDATE_EMAIL) ? '' : $error;
            },
            function ($error) {
                return $this->meta->has($this->data['postid'], $this->authorID) ? $error : '';
            },
        ];
        $error = '';
        while (empty($error) && ! empty($callbacks)) {
            $i = ! isset($i) ? 0 : ++$i;
            $error = call_user_func(array_shift($callbacks), $errors[$i]);
        }
        $this->error = $error;
    }

    /**
     * Sends JSON response error to page.
     */
    private function error()
    {
        $json = array_merge($this->data, ['message' => $this->error, 'class' => 'error']);
        wp_send_json($json);
    }
}
