<?php

namespace Adyen\Core\BusinessLogic\Bootstrap\Aspect;

use Adyen\Core\Infrastructure\ServiceRegister;

/**
 * Class Aspects
 *
 * @template T
 *
 * @package Adyen\Core\BusinessLogic\Bootstrap\Aspect
 */
class Aspects
{
    /**
     * @var T|null
     */
    private $subject;
    /**
     * @var class-string<T>|null
     */
    private $subjectClassName;
    /**
     * @var Aspect
     */
    private $aspect;

    protected function __construct(Aspect $aspect)
    {
        $this->aspect = $aspect;
    }

    public static function run(Aspect $aspect): self
    {
        return new static($aspect);
    }

    public function andRun(Aspect $aspect): self
    {
        $this->aspect = new CompositeAspect($this->aspect);
        $this->aspect->append($aspect);

        return $this;
    }

    /**
     * @param T $subject
     *
     * @return T
     */
    public function beforeEachMethodOfInstance($subject)
    {
        $this->subject = $subject;
        $this->subjectClassName = null;

        return $this;
    }

    /**
     * @param class-string<T> $serviceClass
     *
     * @return T
     */
    public function beforeEachMethodOfService(string $serviceClass)
    {
        $this->subjectClassName = $serviceClass;
        $this->subject = null;

        return $this;
    }

    public function __call($methodName, $arguments)
    {
        if ($this->subject) {
            return $this->aspect->applyOn([$this->subject, $methodName], $arguments);
        }

        return $this->aspect->applyOn(function() use ($methodName, $arguments) {
            $subject = ServiceRegister::getService($this->subjectClassName);

            return call_user_func_array([$subject, $methodName], $arguments);
        });
    }
}
