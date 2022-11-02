<?php
/**
 * CorsResponseListener
 */
namespace Graviton\CommonBundle\Component\Http\Listener;

use Graviton\CommonBundle\CommonUtils;
use Graviton\CommonBundle\Component\Http\Foundation\PsrResponse;
use GuzzleHttp\Psr7\Uri;
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
    private array $allowedHeaders;
    private array $exposedHeaders;
    private array $existingAppendHeaders;
    private array $allowedMethods;

    private ?string $allowedOrigins;
    private ?string $allowedOriginsCredentials;

    /**
     * CorsResponseListener constructor.
     */
    public function __construct(
        array $allowedHeaders,
        array $exposedHeaders,
        array $existingAppendHeaders,
        array $allowedMethods,
        ?string $allowedOrigins,
        ?string $allowedOriginsCredentials
    ) {
        $this->allowedHeaders = $allowedHeaders;
        $this->exposedHeaders = $exposedHeaders;
        $this->existingAppendHeaders = $existingAppendHeaders;
        $this->allowedMethods = $allowedMethods;
        $this->allowedOrigins = $allowedOrigins;

        $this->allowedOriginsCredentials = $allowedOriginsCredentials;
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

        /*** Origin parsing ***/

        $clientOriginValue = $request->headers->get('Origin');

        // defaults
        $originValue = null;
        $allowedCredentials = false;

        if (!is_null($clientOriginValue)) {
            $originHostname = $this->getHostnameFromUri($clientOriginValue);

            if ($this->mirrorBackOriginValue($originHostname)) {
                $originValue = $clientOriginValue;
            }

            $allowedCredentials = $this->isAllowedForCredentials($originHostname);
        }

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

            if ($allowedCredentials) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            } else {
                $response->headers->remove('Access-Control-Allow-Credentials');
            }

            $response->headers->remove('Server');
        }

        // mostly proxied responses (assumed)
        if ($response instanceof PsrResponse) {
            $allowedMethods = implode(', ', $this->allowedMethods);
            $allowedHeaders = implode(', ', $this->allowedHeaders);
            $exposedHeaders = implode(', ', $this->exposedHeaders);

            $psrResponse = $response->getPsrResponse();

            $psrResponse = $psrResponse
                ->withHeader('Access-Control-Expose-Headers', $exposedHeaders)
                ->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
                ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
                ->withoutHeader('Server');

            if (!is_null($originValue)) {
                $psrResponse = $psrResponse
                    ->withHeader('Access-Control-Allow-Origin', $originValue)
                    ->withHeader('Vary', 'Origin');
            } else {
                $psrResponse = $psrResponse
                    ->withoutHeader('Access-Control-Allow-Origin');
            }

            if ($allowedCredentials) {
                $psrResponse = $psrResponse
                    ->withHeader('Access-Control-Allow-Credentials', 'true');
            } else {
                $psrResponse = $psrResponse
                    ->withoutHeader('Access-Control-Allow-Credentials');
            }

            $response->setPsrResponse($psrResponse);
        }
    }

    private function mirrorBackOriginValue(?string $originHostname) : bool
    {
        if (is_null($originHostname) || is_null($this->allowedOrigins) || empty($this->allowedOrigins)) {
            return false;
        }

        return CommonUtils::subjectMatchesStringWildcards($this->allowedOrigins, $originHostname, suffixMatch: true);
    }

    private function isAllowedForCredentials(?string $originHostname) : bool
    {
        if (is_null($originHostname) || is_null($this->allowedOriginsCredentials) || empty($this->allowedOriginsCredentials)) {
            return false;
        }

        return CommonUtils::subjectMatchesStringWildcards($this->allowedOriginsCredentials, $originHostname, suffixMatch: true);
    }

    private function getHostnameFromUri(?string $uri) : ?string {
        if (is_null($uri)) {
            return null;
        }

        try {
            $originUri = new Uri($uri);
            return $originUri->getHost();
        } catch (\Throwable $t) {
            // nothing!
        }
        return null;
    }
}
