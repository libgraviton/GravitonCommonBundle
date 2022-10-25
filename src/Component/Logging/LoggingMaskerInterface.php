<?php

namespace Graviton\CommonBundle\Component\Logging;

interface LoggingMaskerInterface {
    public function getStringsToMask() : array;
}
