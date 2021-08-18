<?php
/**
 * logging middleware used as closure/callable
 */
namespace Graviton\CommonBundle\Component\HttpClient\Guzzle\Middleware;

use Monolog\Logger;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class Logging
{
    /**
     * returns the middleware closure
     *
     * @param LoggerInterface $logger
     * @param string $type
     * @param int    $maxMessageLength
     *
     * @return \Closure
     */
	public static function getCallable(LoggerInterface $logger, $type, $maxMessageLength)
	{
		return function (MessageInterface $message) use ($logger, $type, $maxMessageLength) {
			$startMessage = null;
			if ($message instanceof RequestInterface) {
				$startMessage = sprintf(
					'Proxy %s start: HTTP/%s %s %s',
					$type,
					$message->getProtocolVersion(),
					$message->getMethod(),
					$message->getRequestTarget()
				);
			} elseif ($message instanceof ResponseInterface) {
				$startMessage = sprintf(
					'Proxy %s start: HTTP/%s %s %s',
					$type,
					$message->getProtocolVersion(),
					$message->getStatusCode(),
					$message->getReasonPhrase()
				);
			}

			if (!is_null($startMessage)) {
				$logger->log(Logger::INFO, $startMessage);
			}

			// output headers
			foreach ($message->getHeaders() as $name => $value) {
				$logger->log(
					Logger::INFO,
					sprintf(
						"Proxy %s header: %s => %s",
						$type,
						$name,
						implode(', ', $value)
					)
				);
			}

			$body = $message->getBody();
			if (strlen($body) > $maxMessageLength) {
				$body = substr($body, 0, $maxMessageLength).'[TRUNCATED]';
			}

			$logger->log(
				Logger::INFO,
				sprintf(
					"Proxy %s body: %s",
					$type,
					$body
				)
			);

			if (!is_null($message) && $message->getBody()->isSeekable()) {
				$message->getBody()->rewind();
			}
			return $message;
		};
	}
}
