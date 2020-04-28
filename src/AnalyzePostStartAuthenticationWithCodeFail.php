<?php

namespace Yosmy;

use JsonSerializable;

interface AnalyzePostStartAuthenticationWithCodeFail
{
    /**
     * @param string           $device
     * @param string           $country
     * @param string           $prefix
     * @param string           $number
     * @param JsonSerializable $e
     */
    public function analyze(
        string $device,
        string $country,
        string $prefix,
        string $number,
        JsonSerializable $e
    );
}
