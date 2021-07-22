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

use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Runs an application invoking all the PSR7 middleware and the registered application.
 */
class Server extends \Cake\Http\Server
{

    /**
     * Run the request/response through the Application and its middleware.
     *
     * This will invoke the following methods:
     *
     * - App->bootstrap() - Perform any bootstrapping logic for your application here.
     * - App->middleware() - Attach any application middleware here.
     * - Trigger the 'Server.buildMiddleware' event. You can use this to modify the
     *   from event listeners.
     * - Run the middleware queue including the application.
     *
     * @param \Psr\Http\Message\ServerRequestInterface|null $request The request to use or null.
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue MiddlewareQueue or null.
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \RuntimeException When the application does not make a response.
     */
    public function run(
        ?ServerRequestInterface $request = null,
        ?MiddlewareQueue $middlewareQueue = null
    ): ResponseInterface {
        $this->bootstrap();

        $request = $request ?: ServerRequestFactory::fromGlobals();

        $middleware = $this->app->middleware($middlewareQueue ?? new MiddlewareQueue());
        if ($this->app instanceof PluginApplicationInterface) {
            $middleware = $this->app->pluginMiddleware($middleware);
        }

        $this->dispatchEvent('Server.buildMiddleware', ['middleware' => $middleware]);

        $response = $this->runner->run($middleware, $request, $this->app);

        if ($request instanceof ServerRequest) {
            $request->getSession()->close();
        }

        return $response;
    }
}
