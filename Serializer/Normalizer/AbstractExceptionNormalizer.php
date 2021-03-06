<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\RestBundle\Serializer\Normalizer;

use FOS\RestBundle\Util\ExceptionValueMap;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Ener-Getick <egetick@gmail.com>
 *
 * @internal do not use this class in your code
 */
class AbstractExceptionNormalizer
{
    private $messagesMap;
    private $debug;

    public function __construct(ExceptionValueMap $messagesMap, bool $debug)
    {
        $this->messagesMap = $messagesMap;
        $this->debug = $debug;
    }

    protected function getMessageFromThrowable(\Throwable $throwable, ?int $statusCode = null): string
    {
        $showMessage = $this->messagesMap->resolveException($throwable);

        if ($showMessage || $this->debug) {
            return $throwable->getMessage();
        }

        return array_key_exists($statusCode, Response::$statusTexts) ? Response::$statusTexts[$statusCode] : 'error';
    }
}
