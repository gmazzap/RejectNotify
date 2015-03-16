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
 * Bootstraps the plugin by instantiating, if necessary, the "case" calls  that will handle proper
 * workflow according to current request.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package RejectNotify
 */
class Launcher
{
    const SLUG      = 'reject-notify';
    const CASE_FORM = 'form';
    const CASE_SEND = 'send';
    const CASE_POST = 'post';
    const CASE_LIST = 'list';

    private static $map = [
        self::CASE_FORM => 'FormCase',
        self::CASE_SEND => 'SendCase',
        self::CASE_POST => 'PostCase',
        self::CASE_LIST => 'ListCase',
    ];

    /**
     * @var string Main plugin file path
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Runs on 'admin_init' and bootstrap the whole plugin.
     */
    public function __invoke()
    {
        $capability = apply_filters('reject_notify_admin_cap', 'edit_others_posts');
        $caseType = $this->allowed(defined('DOING_AJAX') && DOING_AJAX);
        $metaHandler = new MetaHandler();
        $caseType === self::CASE_POST and $metaHandler->listenChange();
        if (current_user_can($capability) && $caseType) {
            $this->launch($caseType, $metaHandler);
        }
    }

    /**
     * After having loaded plugin text domain, instantiates the proper case handler class.
     * Class to instantiate can be filtered with the 'reject-notify_case_class' filter, but it has
     * to implement 'CaseInterface' interface.
     * After having been instantiated, init() and run() methods are called on case instance.
     *
     * @param string                       $caseType
     * @param \GM\RejectNotify\MetaHandler $metaHandler
     */
    private function launch($caseType, MetaHandler $metaHandler)
    {
        $domainPath = dirname(plugin_basename($this->path)).'/lang/';
        load_plugin_textdomain('reject-notify', false, $domainPath);
        $class = apply_filters(
            self::SLUG.'_case_class',
            __NAMESPACE__.'\\'.self::$map[$caseType],
            $caseType
        );
        if (class_exists($class) && is_subclass_of($class, '\GM\RejectNotify\CaseInterface')) {
            /** @var \GM\RejectNotify\CaseInterface $case */
            $case = new $class($metaHandler);
            $case->init($this->path) and $case->run();
        }
    }

    /**
     * Return a class constants that is related to the proper case class for current request.
     * If plugin should do anything, return false.
     *
     * @param  bool|string $ajax
     * @return bool|string
     */
    private function allowed($ajax)
    {
        return $ajax ? $this->checkAjax() : $this->checkRegular();
    }

    /**
     * Run on non-ajax requests and returns proper class constants if the current screen is one that
     * should be handled by plugin.
     *
     * @return bool|string
     */
    private function checkRegular()
    {
        $id = -1;
        $screen = get_current_screen();
        $allowedTypes = apply_filters(self::SLUG.'_post_types', ['post']);
        if (! empty($screen)) {
            $edit = strpos($screen->id, 'edit-') === 0;
            $id = $edit && strlen($screen->id) > 5 ? substr($screen->id, 5) : $screen->id;
        }

        return in_array($id, $allowedTypes, true)
            ? ($edit ? self::CASE_LIST : self::CASE_POST)
            : false;
    }

    /**
     * Run on ajax requests and returns proper class constants if the current request is one that
     * should be handled by plugin.
     *
     * @return bool|string
     */
    private function checkAjax()
    {
        $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING);
        $type = strtoupper($method) === 'GET' ? INPUT_GET : INPUT_POST;
        $action = filter_input($type, 'action', FILTER_SANITIZE_STRING);
        $check = $type === INPUT_GET ? '_show_form' : '_send_mail';
        if ($action === self::SLUG.$check) {
            return $type === INPUT_GET ? self::CASE_FORM : self::CASE_SEND;
        }

        return false;
    }
}
