<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Resources;

use InvalidArgumentException;
use Marshmallow\Odoo\Contracts\Client;
use Marshmallow\Odoo\Exceptions\MissingRecordException;
use Marshmallow\Odoo\Support\Domain;

/**
 * A thin, stateless gateway to one Odoo model. Subclasses set $model;
 * the generic resource takes it as a constructor argument.
 */
class Resource
{
    protected string $model;

    /** @var array<string, mixed> */
    protected array $context = [];

    public function __construct(
        protected readonly Client $client,
        ?string $model = null,
    ) {
        if ($model !== null) {
            $this->model = $model;
        }

        if (! isset($this->model)) {
            throw new InvalidArgumentException(static::class.' needs an Odoo model name.');
        }
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Return a copy that sends the given context (e.g. ['lang' => 'nl_NL']) with every call.
     *
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): static
    {
        $clone = clone $this;
        $clone->context = array_merge($this->context, $context);

        return $clone;
    }

    /**
     * Call any method on the model.
     *
     * @param  array<string, mixed>  $params
     * @param  array<int, int>  $ids
     */
    public function call(string $method, array $params = [], array $ids = []): mixed
    {
        if ($this->context !== []) {
            $params['context'] = array_merge($this->context, (array) ($params['context'] ?? []));
        }

        return $this->client->call($this->model, $method, $params, $ids);
    }

    /**
     * Read one record, or throw when it does not exist.
     *
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    public function find(int $id, array $fields = []): array
    {
        $records = $this->get([$id], $fields);

        if ($records === []) {
            throw new MissingRecordException("No {$this->model} record with id {$id}.");
        }

        return $records[0];
    }

    /**
     * Read several records by id.
     *
     * @param  array<int, int>  $ids
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function get(array $ids, array $fields = []): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->records($this->call('read', ['fields' => $fields], $ids));
    }

    public function exists(int $id): bool
    {
        return $this->searchCount(Domain::make()->where('id', $id)) > 0;
    }

    /**
     * Ids of the records matching the domain.
     *
     * @param  Domain|array<int, mixed>  $domain
     * @return array<int, int>
     */
    public function search(Domain|array $domain = [], ?int $limit = null, int $offset = 0, ?string $order = null): array
    {
        $ids = $this->call('search', array_filter([
            'domain' => $this->domain($domain),
            'offset' => $offset,
            'limit' => $limit,
            'order' => $order,
        ], static fn (mixed $value): bool => $value !== null));

        return array_map('intval', is_array($ids) ? $ids : []);
    }

    /**
     * Records matching the domain, with the given fields (all when empty).
     *
     * @param  Domain|array<int, mixed>  $domain
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function searchRead(Domain|array $domain = [], array $fields = [], ?int $limit = null, int $offset = 0, ?string $order = null): array
    {
        return $this->records($this->call('search_read', array_filter([
            'domain' => $this->domain($domain),
            'fields' => $fields,
            'offset' => $offset,
            'limit' => $limit,
            'order' => $order,
        ], static fn (mixed $value): bool => $value !== null)));
    }

    /**
     * The first record matching the domain, or null.
     *
     * @param  Domain|array<int, mixed>  $domain
     * @param  array<int, string>  $fields
     * @return array<string, mixed>|null
     */
    public function first(Domain|array $domain = [], array $fields = [], ?string $order = null): ?array
    {
        return $this->searchRead($domain, $fields, limit: 1, order: $order)[0] ?? null;
    }

    /**
     * @param  Domain|array<int, mixed>  $domain
     */
    public function searchCount(Domain|array $domain = []): int
    {
        return (int) $this->call('search_count', ['domain' => $this->domain($domain)]);
    }

    /**
     * Create one record and return its id.
     *
     * @param  array<string, mixed>  $values
     */
    public function create(array $values): int
    {
        return $this->createMany([$values])[0];
    }

    /**
     * Create several records in one call and return their ids.
     *
     * @param  array<int, array<string, mixed>>  $valuesList
     * @return array<int, int>
     */
    public function createMany(array $valuesList): array
    {
        $ids = $this->call('create', ['vals_list' => array_values($valuesList)]);

        return array_map('intval', is_array($ids) ? $ids : [$ids]);
    }

    /**
     * Write the values to the given record(s).
     *
     * @param  int|array<int, int>  $ids
     * @param  array<string, mixed>  $values
     */
    public function update(int|array $ids, array $values): bool
    {
        return (bool) $this->call('write', ['vals' => $values], (array) $ids);
    }

    /**
     * Unlink the given record(s).
     *
     * @param  int|array<int, int>  $ids
     */
    public function delete(int|array $ids): bool
    {
        return (bool) $this->call('unlink', [], (array) $ids);
    }

    /**
     * Field definitions of the model (fields_get), optionally limited to some attributes.
     *
     * @param  array<int, string>  $attributes
     * @return array<string, array<string, mixed>>
     */
    public function fields(array $attributes = []): array
    {
        $fields = $this->call('fields_get', $attributes === [] ? [] : ['attributes' => $attributes]);

        return is_array($fields) ? $fields : [];
    }

    /**
     * @param  Domain|array<int, mixed>  $domain
     * @return array<int, mixed>
     */
    protected function domain(Domain|array $domain): array
    {
        return $domain instanceof Domain ? $domain->toArray() : array_values($domain);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function records(mixed $result): array
    {
        return is_array($result) ? array_values($result) : [];
    }
}
