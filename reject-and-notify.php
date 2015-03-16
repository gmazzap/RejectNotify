<?php
/*
Plugin Name: Reject & Notify
Plugin URI: https://github.com/Giuseppe-Mazzapica/RejectNotify
Description: Adds a button to post edit page that allows to send a notice to a contributor whose a post was not approved.
Version: 1.0
Author: Giuseppe Mazzapica
Author URI: http://gm.zoomlab.it
Requires at least: 4.0
Tested up to: 4.1.1
Text Domain: reject-notify
Domain Path: /lang/
License: MIT
*/

/**
 * Copyright (c) 2015 Giuseppe Mazzapica
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace GM\RejectNotify;

if (is_admin()) {
    file_exists(__DIR__.'/vendor/autoload.php') and require_once __DIR__.'/vendor/autoload.php';
    global $pagenow;
    if (class_exists('GM\RejectNotify\Launcher')) {
        $launcher = new Launcher(__FILE__);
        in_array($pagenow, ['edit.php', 'post.php'], true) && add_action("load-{$pagenow}", $launcher);
        $pagenow === 'admin-ajax.php' && add_action('admin_init', $launcher);
    }
}
