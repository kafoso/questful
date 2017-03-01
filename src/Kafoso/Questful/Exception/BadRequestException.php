<?php
namespace Kafoso\Questful\Exception;

/**
 * Utilized when client input in malformed or otherwise incorrect. Goes hand-in-hand with the response status "400 Bad
 * Request".
 */
class BadRequestException extends \RuntimeException
{

}
