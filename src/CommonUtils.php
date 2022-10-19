<?php
/**
 * CommonUtils
 */

namespace Graviton\CommonBundle;

/**
 * @author  List of contributors <https://github.com/libgraviton/DeploymentServiceBundle/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class CommonUtils
{
    /**
     * A general function that takes a comma separated string of wildcards,
     * splits them up, creates regexes from it and checks if a given subjects matches *one*
     * of those wildcard regexes
     *
     * @param $wildcards
     * @param $subject
     * @param array $addedList more items to add to wildcards
     *
     * @return true if matches, false otherwise
     */
    public static function subjectMatchesStringWildcards(string|array $wildcards, string $subject, string $method = '', array $addedList = [], $suffixMatch = false) : bool
    {
        $matches = false;

        if (!is_array($wildcards)) {
            $wildcards = array_map('trim', explode(",", $wildcards));
        }

        $wildcards = array_merge(
            $wildcards,
            $addedList
        );

        foreach ($wildcards as $wildcard) {
            // are there methods mentioned?
            $methods = [];
            if (str_contains($wildcard, ':')) {
                $parts = explode(':', $wildcard);

                if (count($parts) != 2) {
                    throw new \RuntimeException('Invalid configuration of wildcard: "'.$wildcard.'"');
                }

                // reset regex to first
                $wildcard = $parts[0];
                $methods = array_map('trim', explode('|', $parts[1]));
            }

            $wildcardRegex = str_replace('*', '(.+)', $wildcard);

            if (!$suffixMatch) {
                // full match
                $regex = '@^'.$wildcardRegex.'$@i';
            } else {
                // suffix match
                $regex = '@(.*)'.$wildcardRegex.'$@i';
            }

            $methodMatches = (empty($methods) || in_array($method, $methods));
            if ($methodMatches && preg_match($regex, $subject) === 1) {
                $matches = true;
                break;
            }
        }

        return $matches;
    }
}
