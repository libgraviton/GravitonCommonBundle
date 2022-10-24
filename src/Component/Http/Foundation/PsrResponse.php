<?php
/**
 * Psr7Response
 */
namespace Graviton\CommonBundle\Component\Http\Foundation;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class PsrResponse extends Response
{

    /**
     * @var ResponseInterface
     */
    private $psrResponse;

    /**
     * @var float
     */
    private $duration;

    /**
     * @var string
     */
    private $upstreamName = 'none';

    /**
     * get PsrResponse
     *
     * @return ResponseInterface PsrResponse
     */
    public function getPsrResponse()
    {
        return $this->psrResponse;
    }

    /**
     * set PsrResponse
     *
     * @param ResponseInterface $psrResponse psrResponse
     *
     * @return void
     */
    public function setPsrResponse(ResponseInterface $psrResponse)
    {
        $this->psrResponse = $psrResponse;
    }

    /**
     * @return string
     */
    public function getUpstreamName(): string {
        return $this->upstreamName;
    }

    /**
     * @param string $upstreamName
     */
    public function setUpstreamName(string $upstreamName): void {
        $this->upstreamName = $upstreamName;
    }
}
