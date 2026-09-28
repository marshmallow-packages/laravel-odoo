<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Support;

use Closure;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Fluent builder for Odoo search domains.
 *
 * Terms added with where*() are combined with AND. orWhere() combines
 * everything before it with the new term using OR, mirroring the
 * precedence of Laravel's query builder. The result is Odoo's prefix
 * notation, e.g. ['|', '&', ['a', '=', 1], ['b', '=', 2], ['c', '=', 3]].
 *
 * @implements Arrayable<int, mixed>
 */
final class Domain implements Arrayable
{
    /** @var array<int, mixed> */
    private array $terms = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * Build a domain from raw Odoo terms, e.g. [['state', '=', 'installed']].
     *
     * @param  array<int, mixed>  $terms
     */
    public static function fromArray(array $terms): self
    {
        $domain = new self;
        $domain->terms = array_values($terms);

        return $domain;
    }

    /**
     * Add a term. With two arguments the operator defaults to "=". A closure
     * receives a fresh Domain and nests it as one AND operand.
     *
     * @param  string|Closure(Domain): void  $field
     */
    public function where(string|Closure $field, mixed $operator = null, mixed $value = null): self
    {
        if ($field instanceof Closure) {
            return $this->whereDomain($this->nested($field));
        }

        [$operator, $value] = $this->normalize($operator, $value, func_num_args() === 2);

        $this->terms[] = [$field, $operator, $value];

        return $this;
    }

    /**
     * Combine everything before with the new term using OR. A closure
     * receives a fresh Domain and nests it as one OR operand.
     *
     * @param  string|Closure(Domain): void  $field
     */
    public function orWhere(string|Closure $field, mixed $operator = null, mixed $value = null): self
    {
        if ($field instanceof Closure) {
            return $this->orWhereDomain($this->nested($field));
        }

        [$operator, $value] = $this->normalize($operator, $value, func_num_args() === 2);

        return $this->or([$field, $operator, $value]);
    }

    /**
     * Negate a domain (or raw terms) as one operand.
     *
     * @param  Domain|array<int, mixed>|Closure(Domain): void  $domain
     */
    public function whereNot(Domain|array|Closure $domain): self
    {
        $terms = $domain instanceof Closure ? $this->nested($domain)->toArray() : ($domain instanceof Domain ? $domain->toArray() : array_values($domain));

        if ($terms === []) {
            return $this;
        }

        $this->terms[] = '!';
        array_push($this->terms, ...self::group($terms));

        return $this;
    }

    /**
     * $min <= field <= $max.
     */
    public function whereBetween(string $field, mixed $min, mixed $max): self
    {
        return $this->where($field, '>=', $min)->where($field, '<=', $max);
    }

    /**
     * field < $min or field > $max.
     */
    public function whereNotBetween(string $field, mixed $min, mixed $max): self
    {
        return $this->whereDomain(['|', [$field, '<', $min], [$field, '>', $max]]);
    }

    /**
     * Records below the given record(s) in a hierarchy (child_of), including themselves.
     *
     * @param  int|array<int, int>  $ids
     */
    public function whereChildOf(string $field, int|array $ids): self
    {
        return $this->where($field, 'child_of', array_values((array) $ids));
    }

    /**
     * Records above the given record(s) in a hierarchy (parent_of), including themselves.
     *
     * @param  int|array<int, int>  $ids
     */
    public function whereParentOf(string $field, int|array $ids): self
    {
        return $this->where($field, 'parent_of', array_values((array) $ids));
    }

    /**
     * @param  array<int, mixed>  $values
     */
    public function whereIn(string $field, array $values): self
    {
        return $this->where($field, 'in', array_values($values));
    }

    /**
     * @param  array<int, mixed>  $values
     */
    public function whereNotIn(string $field, array $values): self
    {
        return $this->where($field, 'not in', array_values($values));
    }

    public function whereNull(string $field): self
    {
        return $this->where($field, '=', false);
    }

    public function whereNotNull(string $field): self
    {
        return $this->where($field, '!=', false);
    }

    public function whereLike(string $field, string $value): self
    {
        return $this->where($field, 'like', $value);
    }

    public function whereILike(string $field, string $value): self
    {
        return $this->where($field, 'ilike', $value);
    }

    /**
     * Nest another domain (or raw terms) as a single AND operand.
     *
     * @param  Domain|array<int, mixed>  $domain
     */
    public function whereDomain(Domain|array $domain): self
    {
        $terms = $domain instanceof Domain ? $domain->toArray() : array_values($domain);

        if ($terms === []) {
            return $this;
        }

        array_push($this->terms, ...self::group($terms));

        return $this;
    }

    /**
     * Nest another domain (or raw terms) as a single OR operand.
     *
     * @param  Domain|array<int, mixed>  $domain
     */
    public function orWhereDomain(Domain|array $domain): self
    {
        $terms = $domain instanceof Domain ? $domain->toArray() : array_values($domain);

        if ($terms === []) {
            return $this;
        }

        return $this->or(...self::group($terms));
    }

    public function isEmpty(): bool
    {
        return $this->terms === [];
    }

    /**
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->terms;
    }

    /**
     * @param  mixed  ...$operand  One operand: a term, or a prefix-notation group spread into its parts
     */
    private function or(mixed ...$operand): self
    {
        if ($this->terms === []) {
            $this->terms = array_values($operand);

            return $this;
        }

        $this->terms = ['|', ...self::group($this->terms), ...array_values($operand)];

        return $this;
    }

    /**
     * Turn a list of implicitly ANDed terms into one prefix-notation operand.
     *
     * @param  array<int, mixed>  $terms
     * @return array<int, mixed>
     */
    private static function group(array $terms): array
    {
        $operands = self::operandCount($terms);

        if ($operands <= 1) {
            return $terms;
        }

        return [...array_fill(0, $operands - 1, '&'), ...$terms];
    }

    /**
     * Count the top-level operands in a prefix-notation list, where each
     * explicit operator consumes the operands that follow it.
     *
     * @param  array<int, mixed>  $terms
     */
    private static function operandCount(array $terms): int
    {
        $count = 0;
        $pending = 0;

        foreach ($terms as $term) {
            if ($term === '&' || $term === '|') {
                $pending++;

                continue;
            }

            if ($term === '!') {
                continue;
            }

            $pending > 0
                ? $pending--
                : $count++;
        }

        return $count;
    }

    /**
     * @param  Closure(Domain): void  $callback
     */
    private function nested(Closure $callback): self
    {
        $domain = new self;
        $callback($domain);

        return $domain;
    }

    /**
     * @return array{0: mixed, 1: mixed}
     */
    private function normalize(mixed $operator, mixed $value, bool $twoArguments): array
    {
        return $twoArguments
            ? ['=', $operator]
            : [$operator, $value];
    }
}
