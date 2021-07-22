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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Customization\Cake\Http;

use Cake\Core\Configure;

use function Laminas\Diactoros\normalizeServer;

/**
 * Factory for making ServerRequest instances.
 *
 * This subclass adds in CakePHP specific behavior to populate
 * the basePath and webroot attributes. Furthermore the Uri's path
 * is corrected to only contain the 'virtual' path for the request.
 */
abstract class ServerRequestFactory extends \Cake\Http\ServerRequestFactory
{
    /**
     * Create a request from the supplied superglobal values.
     *
     * If any argument is not supplied, the corresponding superglobal value will
     * be used.
     *
     * The ServerRequest created is then passed to the fromServer() method in
     * order to marshal the request URI and headers.
     *
     * @param array $server $_SERVER superglobal
     * @param array $query $_GET superglobal
     * @param array $parsedBody $_POST superglobal
     * @param array $cookies $_COOKIE superglobal
     * @param array $files $_FILES superglobal
     *
     * @return \Cake\Http\ServerRequest
     * @throws \InvalidArgumentException for invalid file values
     * @see fromServer()
     */
    public static function fromGlobals(
        ?array $server = null,
        ?array $query = null,
        ?array $parsedBody = null,
        ?array $cookies = null,
        ?array $files = null
    ): ServerRequest {
        $server = normalizeServer($server ?: $_SERVER);

        $server = static::_removeProxyPass($server);

        $uri = static::createUri($server);

        /** @psalm-suppress NoInterfaceProperties */
        $sessionConfig = (array)Configure::read('Session') + [
                'defaults' => 'php',
                'cookiePath' => $uri->webroot,
            ];
        $session = Session::create($sessionConfig);

        /** @psalm-suppress NoInterfaceProperties */
        $request = new ServerRequest([
            'environment' => $server,
            'uri' => $uri,
            'cookies' => $cookies ?: $_COOKIE,
            'query' => $query ?: $_GET,
            'webroot' => $uri->webroot,
            'base' => $uri->base,
            'session' => $session,
            'input' => $server['CAKEPHP_INPUT'] ?? null,
        ]);

        $request = static::marshalBodyAndRequestMethod($parsedBody ?? $_POST, $request);
        $request = static::marshalFiles($files ?? $_FILES, $request);

        return $request;
    }

    /**
     * Remove peraichi_v4 domain proxy_pass
     *
     * @param $server
     *
     * @return array
     */
    private static function _removeProxyPass($server)
    {
        $server['REQUEST_URI'] = str_replace("peraiche_domain_share=" . $_SERVER['HTTP_HOST'], "", $server['REQUEST_URI']);
        $server['QUERY_STRING'] = str_replace("peraiche_domain_share=" . $_SERVER['HTTP_HOST'], "", $server['QUERY_STRING']);

        return $server;
    }
}
