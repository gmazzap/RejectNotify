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
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package RejectNotify
 */
interface CaseInterface
{
    /**
     * Initializes case.
     *
     * @param string $path Plugin main file path
     */
    public function init($path);

    /**
     * Runs case logic.
     */
    public function run();
}
