<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Testing;

final readonly class RecordedCall
{
    /**
     * @param  array<string, mixed>  $params
     * @param  array<int, int>  $ids
     */
    public function __construct(
        public string $model,
        public string $method,
        public array $params = [],
        public array $ids = [],
    ) {}

    public function is(string $model, string $method): bool
    {
        return $this->model === $model && $this->method === $method;
    }
}
