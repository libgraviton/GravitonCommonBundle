<?php

namespace Graviton\CommonBundle\Component\Logging;

class DummyLoggingMasker implements LoggingMaskerInterface {
    public function getStringsToMask() : array {
        return [];
    }
}
