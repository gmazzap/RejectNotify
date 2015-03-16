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
 * Get and set plugin-related information on post meta.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package RejectNotify
 */
class MetaHandler
{
    const KEY = '__reject-notify-meta';

    private $blog_id;

    /**
     * Add hooks
     */
    public function __construct()
    {
        $this->blog_id = get_current_blog_id();
    }

    public function listenChange()
    {
        add_action('post_updated', function ($post_id, $post_after, $post_before) {
            $this->maybeDelete($post_id, $post_after, $post_before);
        }, 10, 3);
    }

    /**
     * Return the post meta to check if post was already rejected.
     *
     * @param  string|int $postId
     * @return string
     */
    public function get($postId)
    {
        return get_post_meta($postId, self::KEY, true);
    }

    /**
     * Return true if given post was already rejected.
     *
     * @param  string|int      $postId
     * @param  string|int|null $userID
     * @return bool
     */
    public function has($postId, $userID = null)
    {
        $val = (int) $this->get($postId);

        return $userID ? intval($userID) === $val : $val > 0;
    }

    /**
     * Add the post meta
     *
     * @param string|int $postId
     * @param string|int $userID
     */
    public function add($postId, $userID = 1)
    {
        if (empty($userID)) {
            $userID = 1;
        }
        update_post_meta($postId, self::KEY, $userID);
    }

    /**
     * Remove post meta
     *
     * @param string|int $postId
     */
    public function delete($postId)
    {
        delete_post_meta($postId, self::KEY);
    }

    /**
     * Remove post meta when post is updated
     *
     * @param string|int $postId
     * @param \WP_Post   $post_after
     * @param \WP_Post   $post_before
     */
    private function maybeDelete($postId, $post_after, $post_before)
    {
        if (
            current_filter() === 'post_updated'
            && $post_before->post_status === 'pending'
            && get_current_blog_id() === $this->blog_id
            && $this->has($postId)
            && $this->changed($post_after, $post_before)
        ) {
            $this->delete($postId);
        }
    }

    /**
     * Check that content had changed before and after the update.
     *
     * @param  \WP_Post $postA
     * @param  \WP_Post $postB
     * @return bool
     */
    private function changed(WP_Post $postA, WP_Post $postB)
    {
        $keys = ['post_status', 'post_content', 'post_author'];
        foreach ($keys as $key) {
            if (trim($postA->$key) !== trim($postB->$key)) {
                return true;
            }
        }

        return false;
    }
}
