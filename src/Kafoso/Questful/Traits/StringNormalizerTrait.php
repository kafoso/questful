<?php
namespace Kafoso\Questful\Traits;

trait StringNormalizerTrait
{
    public function normalizeLineEndingsToCrlf($str)
    {
        return preg_replace('/\r\n?|\n\r?/', "\r\n", $str);
    }

    public function removeTrailingWhitespace($str)
    {
        return preg_replace('/^(.+\S)([\s\t]+)$/m', "$1", $str);
    }
}
