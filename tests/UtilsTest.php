<?php

namespace Tests\Gateway\Controller;

use GatewaySecurityBundle\Utils\Utils;
use PHPUnit\Framework\TestCase;

/**
 * unit tests for utils
 *
 * @package Tests\Gateway\Controller
 */
class UtilsTest extends TestCase
{

    /**
     * @dataProvider wildcardDataProvider
     *
     * @param string  $wildcard
     * @param string  $subject
     * @param boolean $expectedMatch
     */
    public function testSubjectMatchesStringWildcars(
        $wildcard,
        $subject,
        $method,
        $addedList,
        $expectedMatch
    ) {
        $match = Utils::subjectMatchesStringWildcards($wildcard, $subject, $method, $addedList);
        $this->assertSame($expectedMatch, $match);

    }

    public function wildcardDataProvider()
    {
        return [
            [
                'HANS,H*',
                'HANS',
                'GET',
                [],
                true
            ],
            [
                'HANS,H*',
                'hans',
                'GET',
                [],
                true
            ],
            [
                'HANS,H*',
                'ho',
                'GET',
                [],
                true
            ],
            [
                'HANS,H*',
                'i',
                'GET',
                [],
                false
            ],
            [
                '*S,H*',
                'hanz',
                'GET',
                [],
                true
            ],
            [
                '*S,H*',
                'hans',
                'GET',
                [],
                true
            ],
            [
                '*S,H*',
                'dudes',
                'DELETE',
                [],
                true
            ],
            [
                '/*',
                's',
                'DELETE',
                [],
                false
            ],
            [
                '/*',
                '/a',
                'GET',
                [],
                true
            ],
            [
                '/*',
                '/',
                'GET',
                [],
                false
            ],
            [
                '',
                '/',
                'GET',
                [],
                false
            ],
            [
                '',
                '',
                'GET',
                [],
                true
            ],
            [
                '/fred,/franz',
                '/auth',
                'GET',
                ['/auth'],
                true
            ],
            [
                '/fred,/franz',
                '/fred',
                'GET',
                ['/auth'],
                true
            ],
            /* more examples with METHOD restrictions */
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fred',
                'GET',
                [],
                true
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fredder',
                'GET',
                [],
                false
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fredder',
                'POST',
                [],
                true
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fredder',
                'POST',
                [],
                true
            ],
            [
                '/fred*:POST',
                '/fredder',
                'PUT',
                [],
                false
            ],
            [
                '/fred*:POST|PUT',
                '/fredder',
                'PUT',
                [],
                true
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/franz',
                'POST',
                [],
                true
            ],
            [
                '/fred*:POST,/fred:DELETE,/fran*',
                '/fred',
                'DELETE',
                [],
                true
            ],
            [
                '*_RESTRICTED',
                'HANS_RESTRICTED',
                '',
                [],
                true
            ]
        ];
    }
}
