# Sequence

This library provides a different approach to the pipeline pattern.  The pipeline pattern is often compared to a production line, where each stage performs a certain operation on a given payload/subject. Stages can act on, manipulate, decorate, or even replace the payload.

The goals of this library are:
- Be small in size
- Be simple to understand
- Make writing code natural

**Warning**: You may not like the amount of magic that happens internally. With that said, take a look at the examples, and the source code. It's currently at ~150 LOC.

If you would like to know more:
- [Martin Fowler](https://martinfowler.com/articles/collection-pipeline/) has written extensively about collection pipelines.
- [The PHP League](http://pipeline.thephpleague.com/) has a very good pipeline package. This package inspired mine.

## License
MIT License

## Setup
There are two objects

### Sequence Object
```php
$sequence = (new Sequence)
    ->then(Callable)
    ->then(Callable, null);

$sequence->run(Payload);
```

### Payload Object
The payload object is a provided way to magically containerize values that you can send through your sequence. 

```php
$payload = new Payload();

$payload->number = 1;       // Sets a number object to 1
echo $payload->number;      // Will return 1

$payload->setNumber(2);     // Sets the number object to 2
echo $payload->getNumber(); // Will return 2
echo $payload->number;      // Will return 2

echo $payload->notset;      // Will throw exception

$payload->setNotSet(true);  // Set the notset object
echo $payload->notset;      // Will return true
```

## Basic Example
If you're familiar with `League\Pipeline`, you'll be right at home. Here is an exact replica of chaining sequences:
```php
class TimesTwoStage
{
    public function __invoke($payload)
    {
        return $payload * 2;
    }
}

class AddOneStage
{
    public function __invoke($payload)
    {
        return $payload + 1;
    }
}

$sequence = (new Sequence)
    ->then(new TimesTwoStage)
    ->then(new AddOneStage);

// Returns 21
$sequence->run(10);
```

## Callable

```php
class TimesTwoStage
{
    public function __invoke($payload)
    {
        return $payload * 2;
    }
}

$sequence = (new Sequence)
    ->then(new TimesTwoStage)
    ->then(function ($payload) {
        return $payload + 1;
    });

// Returns 21
$sequence->run(10);
```

## Re-usable

```php
class TimesTwoStage
{
    public function __invoke($payload)
    {
        return $payload * 2;
    }
}

class AddOneStage
{
    public function __invoke($payload)
    {
        return $payload + 1;
    }
}

$minusSequence = (new Sequence)
    ->then(function ($payload) {
        return $payload - 2;    // 2
    });

$sequence = (new Sequence)
    ->then(new TimesTwoStage)   // 1
    ->then($minusSequence)      // 2
    ->then(new AddOneStage);    // 3

// Returns 19
echo $sequence->run(10);
```

## Exception handling

```php
$sequence = (new Sequence)
    ->then(function () {
        throw new LogicException();
    });

try {
    $sequence->run($payload);
} catch (LogicException $e) {
    // Handle the exception.
}
```

## Payload

```php

$payload         = new Payload();
$payload->number = 0;

$sequence = (new Sequence)
    ->then(function ($payload) {
        $payload->number = 1;

        return $payload;
    });

$payload = $sequence->run($payload);

// Returns 1
echo $payload->number;
```

## Payload return into key

```php
$payload         = new Payload();
$payload->number = 0;

$sequence = (new Sequence)
    ->then(function () {
        return 1;
    }, 'number');

$payload = $sequence->run($payload);

// Returns 1
echo $payload->number;
```

## Payload dependency injection

```php
$payload         = new Payload();
$payload->number = 0;

// Will automatically input $payload->number into $number
$sequence = (new Sequence)
    ->then(function ($number) {
        return ($number + 1);
    }, 'number');

$payload = $sequence->run($payload);

// Returns 1
echo $payload->number;
```

## Payload dependency injection with typehints

```php
class Multiplier
{
    private $multiple;

    public function __construct(float $multiple)
    {
        $this->multiple = $multiple;
    }

    public function getMultiple(): float
    {
        return $this->multiple;
    }
}

class Multiply
{
    public function __invoke(Multiplier $multiplier, int $number)
    {
        return ($number * $multiplier->getMultiple());
    }
}

$payload             = new Payload();
$payload->multiplier = new Multiplier(2);
$payload->number     = 0;

$sequence = (new Sequence)
    ->then(function ($number) {
        return ($number + 1);
    }, 'number')                        // Will return 1
    ->then(new Multiply, 'number');     // Will return 1 * 2 = 2

$payload = $sequence->run($payload);

// Returns 2
echo $payload->number;
```