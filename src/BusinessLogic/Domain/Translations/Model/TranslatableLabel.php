<?php

namespace Adyen\Core\BusinessLogic\Domain\Translations\Model;

/**
 * Class TranslatableLabel
 *
 * @package Adyen\Core\BusinessLogic\Domain\Translations\Model
 */
class TranslatableLabel
{
    /**
     * @var string
     */
    protected $message;
    /**
     * @var string
     */
    protected $code;
    /**
     * @var string[]
     */
    protected $params;

    /**
     * @param string $message
     * @param string $code
     * @param string[] $params
     */
    public function __construct(string $message, string $code, array $params = [])
    {
        $this->message = $message;
        $this->code = $code;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string[]
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
