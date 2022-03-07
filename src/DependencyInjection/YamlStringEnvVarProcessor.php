<?php

namespace Graviton\CommonBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\Yaml\Yaml;

class YamlStringEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv)
    {
        echo "dude"; die;
        return Yaml::parse($getEnv($name));
    }

    public static function getProvidedTypes()
    {
        return [
            'yaml' => 'array',
        ];
    }
}
