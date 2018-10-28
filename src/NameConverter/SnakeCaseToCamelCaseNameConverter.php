<?php

namespace Barth\SimpleConfigBundle\NameConverter;

class SnakeCaseToCamelCaseNameConverter
{
    public function handle($value) {
        $camelCased = preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
        }, $value);

        return $camelCased;
    }
}
