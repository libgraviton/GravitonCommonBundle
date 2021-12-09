<?php

/**
 * logging middleware used as closure/callable
 */
namespace Graviton\CommonBundle\Component\HttpClient\Guzzle;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface {

    public function onRequest(RequestInterface $request);

    public function onResponse(ResponseInterface $response);

}
