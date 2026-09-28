<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Testing;

use Closure;
use Marshmallow\Odoo\Odoo;
use PHPUnit\Framework\Assert as PHPUnit;

class OdooFake extends Odoo
{
    /**
     * @param  array<string, class-string<\Marshmallow\Odoo\Resources\Resource>>  $resources
     */
    public function __construct(
        private readonly FakeClient $fake,
        array $resources = [],
    ) {
        parent::__construct($fake, $resources);
    }

    /**
     * Script the response for a model method. A Closure receives the RecordedCall.
     */
    public function respond(string $model, string $method, mixed $response): static
    {
        $this->fake->respond($model, $method, $response);

        return $this;
    }

    /**
     * @return array<int, RecordedCall>
     */
    public function recorded(?string $model = null, ?string $method = null): array
    {
        return $this->fake->calls($model, $method);
    }

    /**
     * @param  Closure(RecordedCall): bool|null  $callback
     */
    public function assertCalled(string $model, string $method, ?Closure $callback = null): void
    {
        PHPUnit::assertNotEmpty(
            $this->matching($model, $method, $callback),
            "The expected [{$model}/{$method}] call was not made.",
        );
    }

    /**
     * @param  Closure(RecordedCall): bool|null  $callback
     */
    public function assertCalledTimes(string $model, string $method, int $times, ?Closure $callback = null): void
    {
        $count = count($this->matching($model, $method, $callback));

        PHPUnit::assertSame(
            $times,
            $count,
            "The expected [{$model}/{$method}] call was made {$count} times instead of {$times} times.",
        );
    }

    /**
     * @param  Closure(RecordedCall): bool|null  $callback
     */
    public function assertNotCalled(string $model, string $method, ?Closure $callback = null): void
    {
        PHPUnit::assertEmpty(
            $this->matching($model, $method, $callback),
            "The unexpected [{$model}/{$method}] call was made.",
        );
    }

    public function assertNothingCalled(): void
    {
        $count = count($this->fake->calls());

        PHPUnit::assertSame(0, $count, "{$count} unexpected Odoo call(s) were made.");
    }

    /**
     * @param  Closure(RecordedCall): bool|null  $callback
     * @return array<int, RecordedCall>
     */
    private function matching(string $model, string $method, ?Closure $callback): array
    {
        $calls = $this->fake->calls($model, $method);

        if ($callback === null) {
            return $calls;
        }

        return array_values(array_filter($calls, $callback));
    }
}
