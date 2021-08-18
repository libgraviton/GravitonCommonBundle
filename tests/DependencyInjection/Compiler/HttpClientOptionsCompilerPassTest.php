<?php
/**
 * HttpClientOptionsCompilerPass class file
 */

namespace Graviton\CommonBundleTest\DependencyInjection\CompilerPass;

use Graviton\CommonBundle\DependencyInjection\Compiler\HttpClient\HttpClientOptionsCompilerPass;
use PHPUnit\Framework\TestCase;

class HttpClientOptionsCompilerPassTest extends TestCase
{
    /**
     * test the compiler pass
     *
     * @dataProvider httpClientDataProvider
     *
     * @param array  $env                   temp env
     * @param array  $paramValue            expected param to set
     * @param string $containerProxySetting container setting for proxy
     * @param string $containerNoProxy      container setting for noproxy
     *
     * @return void
     */
    public function testParameterSetting($env, $paramValue, $containerProxySetting = null, $containerNoProxy = null)
    {
        $backEnv = $_ENV;
        $_ENV = $env;

        $containerDouble = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
                                ->disableOriginalConstructor()
                                ->getMock();

        $containerDouble
            ->expects($this->atLeast(3))
            ->method('getParameter')
            ->with(
                $this->logicalOr(
                    $this->equalTo('graviton.proxy'),
                    $this->equalTo('graviton.noproxy'),
                    $this->equalTo('graviton.common.http_client.verify_peer')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($paramName) use ($containerProxySetting, $containerNoProxy) {
                        if ($paramName == 'graviton.proxy') {
                            return $containerProxySetting;
                        }
                        if ($paramName == 'graviton.common.http_client.verify_peer') {
                            return false;
                        }
                        return $containerNoProxy;
                    }
                )
            );

        $containerDouble
            ->expects($this->exactly(1))
            ->method('setParameter')
            ->with(
                'graviton.common.http_client.base_options',
                $paramValue
            );

        try {
            $compilerPass = new HttpClientOptionsCompilerPass();
            $compilerPass->process($containerDouble);
        } finally {
            $_ENV = $backEnv;
        }
    }

    /**
     * data provider for param settings
     *
     * @return array data
     */
    public function httpClientDataProvider()
    {
        return [
            'noproxy' => [
                [],
                [
                    'verify' => false
                ]
            ],
            'old-setting-format' => [
                [
                    'HTTP_PROXY' => 'other-proxy',
                    'GRAVITON_PROXY_CURLOPTS' => "{proxy: 'http://myproxy:8080/', noproxy: '.localhost, .vcap.me'}"
                ],
                [
                    'verify' => false,
                    'proxy' => [
                        'http' => 'http://myproxy:8080/',
                        'https' => 'http://myproxy:8080/',
                        'no' => [
                            '.localhost',
                            '.vcap.me'
                        ]
                    ]
                ]
            ],
            'only-system-settings' => [
                [
                    'HTTP_PROXY' => 'other-proxy',
                    'HTTPS_PROXY' => 'https-proxy',
                    'NO_PROXY' => 'test,other-host'
                ],
                [
                    'verify' => false,
                    'proxy' => [
                        'http' => 'other-proxy',
                        'https' => 'https-proxy',
                        'no' => [
                            'test',
                            'other-host'
                        ]
                    ]
                ]
            ],
            'system-settings-with-params' => [
                [
                    'HTTP_PROXY' => 'other-proxy',
                    'HTTPS_PROXY' => 'https-proxy',
                    'NO_PROXY' => 'test,other-host'
                ],
                [
                    'verify' => false,
                    'proxy' => [
                        'http' => 'http://real-proxy-to-use',
                        'https' => 'http://real-proxy-to-use',
                        'no' => [
                            'host1',
                            '.tld'
                        ]
                    ]
                ],
                'http://real-proxy-to-use',
                'host1,.tld'
            ],
            'system-settings-with-params-array' => [
                [
                    'HTTP_PROXY' => 'other-proxy',
                    'HTTPS_PROXY' => 'https-proxy',
                    'NO_PROXY' => 'test,other-host'
                ],
                [
                    'verify' => false,
                    'proxy' => [
                        'http' => 'http://real-proxy-to-use',
                        'https' => 'http://real-proxy-to-use',
                        'no' => [
                            'host1',
                            '.tld'
                        ]
                    ]
                ],
                'http://real-proxy-to-use',
                ['host1 ','.tld ']
            ]
        ];
    }
}
