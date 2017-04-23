<?php
declare(strict_types=1);

namespace Snelling\Sequence;

use Closure;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;

class Sequence
{
    private $steps = [];

    public function __invoke($payload)
    {
        return $this->run($payload);
    }

    public function then($callable, string $payloadKey = null): Sequence
    {
        $sequence          = clone $this;
        $sequence->steps[] = [$callable, $payloadKey];

        return $sequence;
    }

    public function run($payload)
    {

        foreach ($this->steps as list($step, $payloadKey)) {
            if (!is_callable($step)) {
                throw new RuntimeException('Step is not Callable');
            }
            $payload = $this->processSteps($payload, $step, $payloadKey);
        }

        return $payload;
    }

    private function processSteps($payload, $step, $payloadKey)
    {
        if (!($payload instanceof Payload)) {
            return $this->processSimple($payload, $step);
        }

        return $this->processReflection($payload, $step, $payloadKey);
    }

    private function processSimple($payload, $step)
    {
        $payload = $step($payload);

        return $payload;
    }

    private function processReflection($payload, $step, $payloadKey)
    {
        if ($step instanceof Closure) {
            $reflection = new ReflectionFunction($step);
        } else {
            if (!method_exists($step, '__invoke')) {
                throw new RuntimeException('Method __invoke does not exist in class ' . $step);
            }
            $reflection = new ReflectionMethod($step, '__invoke');
        }

        $parameters   = $this->getReflectionParameters($reflection->getParameters());
        $parameterMap = $this->mapReflectionParameters($payload, $parameters);

        if ($payloadKey !== null) {
            $value = call_user_func_array($step, $parameterMap);
            if ($value instanceof Payload) {
                throw new RuntimeException('Recursion error. Cannot return a Payload instance into payload key.');
            }
            if ($value !== null) {
                $payload->$payloadKey = $value;
            }
        } else {
            $result = call_user_func_array($step, $parameterMap);
            if ($result !== null) {
                $payload = $result;
            }
        }

        return $payload;
    }

    private function getReflectionParameters(array $reflections): array
    {
        $parameters = [];
        foreach ($reflections as $reflectionParameter) {
            $name = $reflectionParameter->name;
            $type = null;
            if ($reflectionParameter->hasType()) {
                $type = (string) $reflectionParameter->getType();
            }
            $parameters[] = [$name, $type];
        }

        return $parameters;
    }

    private function mapReflectionParameters($payload, array $parameters): array
    {
        $parameterMap = [];
        foreach ($parameters as list($name, $type)) {
            if ($name === 'payload') {
                $parameterMap[] = $payload;
                continue;
            }

            $payloadParameter = $payload->$name;
            $parameterMap[]   = $payloadParameter;
            if ($type !== null && ((function_exists('is_' . $type) && !call_user_func('is_' . $type, $payloadParameter)) && !($payloadParameter instanceOf $type))) {
                throw new RuntimeException('Payload parameter `' . $name . '` is of type `' . gettype($payloadParameter) . '` while the method expected `' . $type . '`');
            }
        }

        return $parameterMap;
    }
}