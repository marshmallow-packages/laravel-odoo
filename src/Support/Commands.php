<?php

declare(strict_types=1);

namespace Marshmallow\Odoo\Support;

/**
 * Odoo x2many field commands, for one2many and many2many values in
 * create() and write(): [0, 0, vals], [1, id, vals], [2, id], [3, id],
 * [4, id], [5], [6, 0, ids].
 */
final class Commands
{
    /**
     * Create a new related record: [0, 0, vals].
     *
     * @param  array<string, mixed>  $values
     * @return array{0: int, 1: int, 2: array<string, mixed>}
     */
    public static function create(array $values): array
    {
        return [0, 0, $values];
    }

    /**
     * Update an existing related record: [1, id, vals].
     *
     * @param  array<string, mixed>  $values
     * @return array{0: int, 1: int, 2: array<string, mixed>}
     */
    public static function update(int $id, array $values): array
    {
        return [1, $id, $values];
    }

    /**
     * Remove the relation and delete the record: [2, id].
     *
     * @return array{0: int, 1: int}
     */
    public static function delete(int $id): array
    {
        return [2, $id];
    }

    /**
     * Remove the relation but keep the record: [3, id].
     *
     * @return array{0: int, 1: int}
     */
    public static function unlink(int $id): array
    {
        return [3, $id];
    }

    /**
     * Add a relation to an existing record: [4, id].
     *
     * @return array{0: int, 1: int}
     */
    public static function link(int $id): array
    {
        return [4, $id];
    }

    /**
     * Remove all relations, keeping the records: [5].
     *
     * @return array{0: int}
     */
    public static function clear(): array
    {
        return [5];
    }

    /**
     * Replace all relations with the given ids: [6, 0, ids].
     *
     * @param  array<int, int>  $ids
     * @return array{0: int, 1: int, 2: array<int, int>}
     */
    public static function set(array $ids): array
    {
        return [6, 0, array_values($ids)];
    }
}
