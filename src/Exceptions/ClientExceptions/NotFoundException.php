<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Http\Exceptions\ClientExceptions;

use Spiral\Http\Exceptions\ClientException;

/**
 * HTTP 404 exception.
 */
class NotFoundException extends ClientException
{
    /** @var int */
    protected $code = ClientException::NOT_FOUND;

    /**
     * @param string $message
     */
    public function __construct(string $message = "")
    {
        parent::__construct($this->code, $message);
    }
}