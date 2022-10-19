<?php

namespace Graviton\CommonBundleTest;

use Graviton\CommonBundle\CommonUtils;
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
        $suffixMatch,
        $expectedMatch
    ) {
        $match = CommonUtils::subjectMatchesStringWildcards($wildcard, $subject, $method, $addedList, $suffixMatch);
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
                false,
                true
            ],
            [
                'HANS,H*',
                'hans',
                'GET',
                [],
                false,
                true
            ],
            [
                'HANS,H*',
                'ho',
                'GET',
                [],
                false,
                true
            ],
            [
                'HANS,H*',
                'i',
                'GET',
                [],
                false,
                false
            ],
            [
                '*S,H*',
                'hanz',
                'GET',
                [],
                false,
                true
            ],
            [
                '*S,H*',
                'hans',
                'GET',
                [],
                false,
                true
            ],
            [
                '*S,H*',
                'dudes',
                'DELETE',
                [],
                false,
                true
            ],
            [
                '/*',
                's',
                'DELETE',
                [],
                false,
                false
            ],
            [
                '/*',
                '/a',
                'GET',
                [],
                false,
                true
            ],
            [
                '/*',
                '/',
                'GET',
                [],
                false,
                false
            ],
            [
                '',
                '/',
                'GET',
                [],
                false,
                false
            ],
            [
                '',
                '',
                'GET',
                [],
                false,
                true
            ],
            [
                '/fred,/franz',
                '/auth',
                'GET',
                ['/auth'],
                false,
                true
            ],
            [
                '/fred,/franz',
                '/fred',
                'GET',
                ['/auth'],
                false,
                true
            ],
            /* more examples with METHOD restrictions */
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fred',
                'GET',
                [],
                false,
                true
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fredder',
                'GET',
                [],
                false,
                false
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fredder',
                'POST',
                [],
                false,
                true
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/fredder',
                'POST',
                [],
                false,
                true
            ],
            [
                '/fred*:POST',
                '/fredder',
                'PUT',
                [],
                false,
                false
            ],
            [
                '/fred*:POST|PUT',
                '/fredder',
                'PUT',
                [],
                false,
                true
            ],
            [
                '/fred*:POST,/fred:GET,/fran*',
                '/franz',
                'POST',
                [],
                false,
                true
            ],
            [
                '/fred*:POST,/fred:DELETE,/fran*',
                '/fred',
                'DELETE',
                [],
                false,
                true
            ],
            [
                '*_RESTRICTED',
                'HANS_RESTRICTED',
                '',
                [],
                false,
                true
            ],
            [
                '.vcap.me,localhost',
                'http://localhost',
                '',
                [],
                true,
                true
            ],
            [
                '.vcap.me,localhost',
                'http://hans.vcap.me',
                '',
                [],
                true,
                true
            ],
            [
                '.vcap.me,localhost',
                'http://localhost:3000',
                '',
                [],
                true,
                false
            ]
        ];
    }
}
