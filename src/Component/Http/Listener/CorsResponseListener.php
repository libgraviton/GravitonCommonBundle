<?php
/**
 * CorsResponseListener
 */
namespace Graviton\CommonBundle\Component\Http\Listener;

use Graviton\CommonBundle\Component\Http\Foundation\PsrResponse;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class CorsResponseListener
 *
 * Simple Listener that adds CORS headers if user is not authenticated, so just for /auth
 *
 * @package GatewaySecurityBundle\Listener
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class CorsResponseListener
{
    private bool $allowCredentials;
    private array $allowedHeaders;
    private array $exposedHeaders;
    private array $allowedMethods;
    private array $allowedOrigins = [];

    /**
     * these will be added to the exposed header cors header even on proxied requests
     */
    public const HEADER_EXPOSED_PROXIED = 'X-Gateway-Audit-Id';

    /**
     * CorsResponseListener constructor.
     */
    public function __construct(
        bool $allowCredentials,
        array $allowedHeaders,
        array $exposedHeaders,
        array $allowedMethods,
        ?string $allowedOrigins
    ) {
        $this->allowCredentials = $allowCredentials;
        $this->allowedHeaders = $allowedHeaders;
        $this->exposedHeaders = $exposedHeaders;
        $this->allowedMethods = $allowedMethods;

        if (!is_null($allowedOrigins)) {
            $this->allowedOrigins = array_map(
                function($url) {
                    return strtolower(trim($url));
                }, explode(',', $allowedOrigins)
            );
        }
    }

    /**
     * on kernel response (and if loginAction has set it correctly), we change the response body
     * to whatever upstream provides us with the configured path..
     *
     * @param ResponseEvent $event event
     *
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        $originValue = $this->getAllowedOriginHeader($request);

        // internal responses
        if (!$response instanceof PsrResponse) {
            // Case where OPTIONS has been sent and we want to redirect -> overwrite with cors response for OPTIONS
            if (in_array($request->getMethod(), ['OPTIONS', 'HEAD'])) {
                $response->setStatusCode(Response::HTTP_NO_CONTENT);
                $response->setContent('');
            }

            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $response->headers->set('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));

            if (!is_null($originValue)) {
                $response->headers->set('Access-Control-Allow-Origin', $originValue);
                $response->headers->set('Vary', 'Origin');
            } else {
                // unset!
                $response->headers->remove('Access-Control-Allow-Origin');
                $response->headers->remove('Vary');
            }

            if (!$this->allowCredentials) {
                $response->headers->remove('Access-Control-Allow-Credentials');
            }

            $response->headers->remove('Server');
        }

        // mostly proxied responses (assumed)
        if ($response instanceof PsrResponse) {
            $exposedHeaders = $response->getPsrResponse()->getHeaderLine('Access-Control-Expose-Headers');
            if (empty($exposedHeaders)) {
                $exposedHeaders = self::HEADER_EXPOSED_PROXIED;
            } else {
                $exposedHeaders .= ', '.self::HEADER_EXPOSED_PROXIED;
            }

            $psrResponse = $response->getPsrResponse();

            $psrResponse = $psrResponse
                ->withHeader('Access-Control-Expose-Headers', $exposedHeaders)
                ->withoutHeader('Server');

            if (!is_null($originValue)) {
                $psrResponse = $psrResponse
                    ->withHeader('Access-Control-Allow-Origin', $originValue)
                    ->withHeader('Vary', 'Origin');
            } else {
                $psrResponse = $psrResponse
                    ->withoutHeader('Access-Control-Allow-Origin');
            }

            if (!$this->allowCredentials) {
                $psrResponse = $psrResponse
                    ->withoutHeader('Access-Control-Allow-Credentials');
            }

            $response->setPsrResponse($psrResponse);
        }
    }

    private function getAllowedOriginHeader(Request $request)
    {
        if (is_null($this->allowedOrigins) || empty($this->allowedOrigins)) {
            return null;
        }

        $origin = $request->headers->get('Origin');
        if (is_null($origin)) {
            return null;
        }

        try {
            $originUri = new Uri($origin);

            $isAllowed = false;
            $originHost = $originUri->getHost();
            foreach ($this->allowedOrigins as $allowedOrigin) {
                if ($allowedOrigin == $origin || $allowedOrigin == $originHost || str_ends_with($originHost, $allowedOrigin)) {
                    $isAllowed = true;
                    break;
                }
            }

            if ($isAllowed) {
                // matches -> return original!
                return $origin;
            }
        } catch (\Throwable $t) {
            // nothing!
        }

        $oneAllowedHost = array_pop($this->allowedOrigins);
        // valid url?
        try {
            $oneAllowedHost = new Uri($oneAllowedHost);
            $oneAllowedHost = (string) $oneAllowedHost;
        } catch (\Throwable $t) {
            if (str_starts_with($oneAllowedHost, '.')) {
                $oneAllowedHost = 'www'.$oneAllowedHost;
            }

            $oneAllowedHost = (string) $originUri->withHost($oneAllowedHost);
        }
        
        return $oneAllowedHost;
    }
}
