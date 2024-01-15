<?php
/**
 * SecurityFormatter
 */
namespace Graviton\CommonBundle\Component\Logging;

use Graviton\CommonBundle\Component\Audit\AuditIdStorage;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

/**
 * Class SecurityFormatter
 *
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class SecurityFormatter extends LineFormatter
{

    private AuditIdStorage $auditIdStorage;
    private ?LoggingMaskerInterface $loggingMasker = null;

    /**
     * @param string $format                     The format of the message
     * @param string $dateFormat                 The format of the timestamp: one supported by DateTime::format
     * @param bool   $allowInlineLineBreaks      Whether to allow inline line breaks in log entries
     * @param bool   $ignoreEmptyContextAndExtra if to ignore
     */
    public function __construct(
        $format = null,
        $dateFormat = null,
        $allowInlineLineBreaks = false,
        $ignoreEmptyContextAndExtra = false
    ) {
        parent::__construct($format, $dateFormat, false, $ignoreEmptyContextAndExtra);
    }

    public function setLoggingMasker(LoggingMaskerInterface $loggingMasker) {
        $this->loggingMasker = $loggingMasker;
    }

    /**
     * set auditid storage
     *
     * @param AuditIdStorage $auditIdStorage audit storage
     */
    public function setAuditIdStorage(AuditIdStorage $auditIdStorage) {
        $this->auditIdStorage = $auditIdStorage;
    }

    /**
     * format our line
     *
     * @param array $record record
     *
     * @return string record
     */
    public function format(LogRecord $record): string
    {
        // insert request id
        $record['extra']['auditId'] = $this->auditIdStorage->getString();

        $line = parent::format($record);

        if (!is_null($this->loggingMasker)) {
            return str_replace(
                $this->loggingMasker->getStringsToMask(),
                "[*** MASKED ***]",
                $line
            );
        }

        return $line;
    }
}
