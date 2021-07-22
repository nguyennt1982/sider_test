<?php

declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Customization\Cake\Http;

/**
 * This class is a wrapper for the native PHP session functions. It provides
 * several defaults for the most common session configuration
 * via external handlers and helps with using session in CLI without any warnings.
 *
 * Sessions can be created from the defaults using `Session::create()` or you can get
 * an instance of a new session by just instantiating this class and passing the complete
 * options you want to use.
 *
 * When specific options are omitted, this class will take its defaults from the configuration
 * values from the `session.*` directives in php.ini. This class will also alter such
 * directives when configuration values are provided.
 */
class Session extends \Cake\Http\Session
{
    /**
     * Returns true if the session is no longer valid because the last time it was
     * accessed was after the configured timeout.
     *
     * @return bool
     */
    protected function _timedOut(): bool
    {
        $timeStoredRedis = $this->read('Config.time');
        $result = false;

        $checkTime = $timeStoredRedis !== null && $this->_lifetime > 0;

        $currentTime = time();
        if ($checkTime && ($currentTime > $timeStoredRedis)) {
            $result = true;
        }

        $timeUpdateToRedis = $currentTime + ($this->_lifetime * 60);
        $this->write('Config.time', $timeUpdateToRedis);

        return $result;
    }
}
