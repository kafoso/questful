<?php
namespace Kafoso\Questful\Exception;

class FormattingHelper
{
    public static function found($value)
    {
        if (is_float($value) || is_int($value)) {
            return sprintf(
                "(%s) %s",
                gettype($value),
                strval($value)
            );
        }
        if (is_bool($value)) {
            return sprintf(
                "(%s) %s",
                gettype($value),
                ($value ? "true" : "false")
            );
        }
        if (is_string($value)) {
            return sprintf(
                "(%s) %s",
                gettype($value),
                $value
            );
        }
        if (is_null($value)) {
            return "(null) null";
        }
        if (is_array($value)) {
            return sprintf(
                "(array) Array(%s)",
                count($value)
            );
        }
        if (is_object($value)) {
            return sprintf(
                "(object) \\%s",
                get_class($value)
            );
        }
        if (is_resource($value)) {
            return sprintf(
                "(resource) %s",
                strval($value)
            );
        }
        return "(unkown)";
    }
}
