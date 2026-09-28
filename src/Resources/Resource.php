<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Resources;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
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

    public function withLang(string $lang): static
    {
        return $this->withContext(['lang' => $lang]);
    }

    public function withTimezone(string $timezone): static
    {
        return $this->withContext(['tz' => $timezone]);
    }

    /**
     * Act in the given company (the first id) with access to all given companies.
     *
     * @param  int|array<int, int>  $companyIds
     */
    public function withCompany(int|array $companyIds): static
    {
        $companyIds = array_values((array) $companyIds);

        return $this->withContext([
            'company_id' => $companyIds[0],
            'allowed_company_ids' => $companyIds,
        ]);
    }

    /**
     * Include archived records in searches (active_test off).
     */
    public function withArchived(): static
    {
        return $this->withContext(['active_test' => false]);
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
     * @param  bool  $loadNames  Return many2one fields as [id, display_name] (Odoo's default); false returns plain ids
     * @return array<string, mixed>
     */
    public function find(int $id, array $fields = [], bool $loadNames = true): array
    {
        $records = $this->get([$id], $fields, $loadNames);

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
     * @param  bool  $loadNames  Return many2one fields as [id, display_name] (Odoo's default); false returns plain ids
     * @return array<int, array<string, mixed>>
     */
    public function get(array $ids, array $fields = [], bool $loadNames = true): array
    {
        if ($ids === []) {
            return [];
        }

        $params = ['fields' => $fields];

        if (! $loadNames) {
            $params['load'] = null;
        }

        return $this->records($this->call('read', $params, $ids));
    }

    /**
     * Display names keyed by id.
     *
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    public function displayNames(array $ids): array
    {
        $names = [];

        foreach ($this->get($ids, ['display_name']) as $record) {
            $names[(int) $record['id']] = (string) ($record['display_name'] ?? '');
        }

        return $names;
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
     * searchRead() as a Collection.
     *
     * @param  Domain|array<int, mixed>  $domain
     * @param  array<int, string>  $fields
     * @return Collection<int, array<string, mixed>>
     */
    public function collect(Domain|array $domain = [], array $fields = [], ?int $limit = null, int $offset = 0, ?string $order = null): Collection
    {
        return new Collection($this->searchRead($domain, $fields, $limit, $offset, $order));
    }

    /**
     * Page through all matching records, $size at a time. Return false from
     * the callback to stop. Pages are ordered by id unless $order is given.
     *
     * @param  Closure(array<int, array<string, mixed>>, int): (bool|null|void)  $callback
     * @param  Domain|array<int, mixed>  $domain
     * @param  array<int, string>  $fields
     */
    public function chunk(int $size, Closure $callback, Domain|array $domain = [], array $fields = [], ?string $order = null): bool
    {
        $page = 1;

        foreach ($this->pages($size, $domain, $fields, $order) as $records) {
            if ($callback($records, $page++) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * All matching records as a LazyCollection, fetched $chunkSize at a time.
     *
     * @param  Domain|array<int, mixed>  $domain
     * @param  array<int, string>  $fields
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function lazy(Domain|array $domain = [], array $fields = [], int $chunkSize = 100, ?string $order = null): LazyCollection
    {
        return LazyCollection::make(function () use ($domain, $fields, $chunkSize, $order) {
            foreach ($this->pages($chunkSize, $domain, $fields, $order) as $records) {
                yield from $records;
            }
        });
    }

    /**
     * Group and aggregate (formatted_read_group), e.g.
     * readGroup($domain, ['partner_id'], ['amount_total:sum', '__count']).
     *
     * @param  Domain|array<int, mixed>  $domain
     * @param  array<int, string>  $groupBy  Field names, optionally with a granularity: 'invoice_date:month'
     * @param  array<int, string>  $aggregates  'field:agg' specs (sum, avg, min, max, count, count_distinct, array_agg) and '__count'
     * @param  array<int, mixed>  $having
     * @return array<int, array<string, mixed>>
     */
    public function readGroup(Domain|array $domain, array $groupBy, array $aggregates = ['__count'], array $having = [], ?int $limit = null, int $offset = 0, ?string $order = null): array
    {
        return $this->records($this->call('formatted_read_group', array_filter([
            'domain' => $this->domain($domain),
            'groupby' => array_values($groupBy),
            'aggregates' => array_values($aggregates),
            'having' => array_values($having),
            'offset' => $offset,
            'limit' => $limit,
            'order' => $order,
        ], static fn (mixed $value): bool => $value !== null)));
    }

    /**
     * Search by display name (name_search); returns display names keyed by id.
     *
     * @param  Domain|array<int, mixed>  $domain
     * @return array<int, string>
     */
    public function nameSearch(string $name, Domain|array $domain = [], ?int $limit = null, string $operator = 'ilike'): array
    {
        $pairs = $this->call('name_search', array_filter([
            'name' => $name,
            'domain' => $this->domain($domain),
            'operator' => $operator,
            'limit' => $limit,
        ], static fn (mixed $value): bool => $value !== null));

        $names = [];

        foreach (is_array($pairs) ? $pairs : [] as $pair) {
            if (is_array($pair) && isset($pair[0])) {
                $names[(int) $pair[0]] = (string) ($pair[1] ?? '');
            }
        }

        return $names;
    }

    /**
     * Whether the API key's user may perform the operation (read, write, create, unlink)
     * on the model, or on the given records.
     *
     * @param  array<int, int>  $ids
     */
    public function hasAccess(string $operation, array $ids = []): bool
    {
        return (bool) $this->call('has_access', ['operation' => $operation], $ids);
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
     * Archive the given record(s) instead of deleting them (action_archive).
     *
     * @param  int|array<int, int>  $ids
     */
    public function archive(int|array $ids): bool
    {
        $this->call('action_archive', [], (array) $ids);

        return true;
    }

    /**
     * Restore archived record(s) (action_unarchive).
     *
     * @param  int|array<int, int>  $ids
     */
    public function unarchive(int|array $ids): bool
    {
        $this->call('action_unarchive', [], (array) $ids);

        return true;
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
     * @param  array<int, string>  $fields
     * @return iterable<int, array<int, array<string, mixed>>>
     */
    protected function pages(int $size, Domain|array $domain, array $fields, ?string $order): iterable
    {
        if ($size < 1) {
            throw new InvalidArgumentException('Chunk size must be at least 1.');
        }

        $offset = 0;

        do {
            $records = $this->searchRead($domain, $fields, $size, $offset, $order ?? 'id asc');

            if ($records !== []) {
                yield $records;
            }

            $offset += $size;
        } while (count($records) === $size);
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
