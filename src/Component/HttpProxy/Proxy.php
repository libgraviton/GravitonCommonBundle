<?php
/**
 * Proxy
 */
namespace Graviton\PhpProxy;

use GuzzleHttp\Client;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * proxies request from one place to another.. thanks jenssegers for the first approach ;-)
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Proxy
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $cleanHeaders;

    /**
     * @var array
     */
    private $cleanResponseHeaders;

    /**
     * @var array
     */
    private $addResponseHeaders;

    /**
     * Proxy constructor.
     *
     * @param Client $client client
     * @param array  $cleanHeaders         headers to remove before we send request upstream
     * @param array  $cleanResponseHeaders headers to remove from response
     * @param array  $addResponseHeaders   headers to add to response before returning
     */
    public function __construct(
        Client $client,
        $cleanHeaders = [],
        $cleanResponseHeaders = [],
        $addResponseHeaders = []
    ) {
        $this->client = $client;
        $this->cleanHeaders = array_map('strtolower', $cleanHeaders);
        $this->cleanResponseHeaders = array_map('strtolower', $cleanResponseHeaders);
        $this->addResponseHeaders = $addResponseHeaders;
    }

    /**
     * Prepare the proxy to forward a request instance.
     *
     * @param  ServerRequestInterface $request request
     *
     * @return $this
     */
    public function forward(ServerRequestInterface $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Forward the request to the target url and return the response.
     *
     * @param string $target target
     *
     * @throws LogicException
     * @return ResponseInterface
     */
    public function to($target)
    {
        if (is_null($this->request)) {
            throw new \LogicException('Missing request instance.');
        }

        $target = new Uri($target);

        // Overwrite target scheme and host.
        $uri = $this->request->getUri()
            ->withScheme($target->getScheme())
            ->withHost($target->getHost());

        // Check for custom port.
        if ($port = $target->getPort()) {
            $uri = $uri->withPort($port);
        }

        // Check for subdirectory.
        if ($path = $target->getPath()) {
            $uri = $uri->withPath(rtrim($path, '/') . '/' . ltrim($uri->getPath(), '/'));
        }

        if (!empty($this->request->getQueryParams())) {
            // special case for rql
            $queryParams = $this->request->getQueryParams();

            if (count($queryParams) == 1 && empty(array_shift($queryParams))) {
                $queryKeys = array_keys($this->request->getQueryParams());
                $uri = $uri->withQuery($queryKeys[0]);
            } else {
                $uri = $uri->withQuery(http_build_query($this->request->getQueryParams()));
            }
        }

        $request = $this->request->withUri($uri);

        // make sure we don't send empty headers or stuff to purge
        foreach ($request->getHeaders() as $headerName => $headerValue) {
            if (in_array(strtolower($headerName), $this->cleanHeaders) || empty($headerValue[0])) {
                $request = $request->withoutHeader($headerName);
            }
        }

        $response = $this->client->send($request);

        foreach ($this->cleanResponseHeaders as $headerName) {
            $response = $response->withoutHeader($headerName);
        }

        foreach ($this->addResponseHeaders as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }

        return $response;
    }
}
