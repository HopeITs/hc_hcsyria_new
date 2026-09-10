<?php

namespace App\Queries;

use App\Models\Initiative;
use App\Models\SubWhatCategory;
use App\Models\WhatCategory;
use App\Models\WhoCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/*
 * The what/who/sub-what category lookups used by the admin forms.
 *
 * These three tables are read in 25 live controller methods across
 * InitiativesController, SchoolController, ChildrenController, LeapController,
 * StudyController, DiscController and TestimoniesController — sometimes as
 * Eloquent, sometimes as DB::table, sometimes as raw SQL with an interpolated
 * id. One place, one behaviour.
 */
class CategoryQuery
{
    private const MODELS = [
        'what'     => WhatCategory::class,
        'who'      => WhoCategory::class,
        'sub_what' => SubWhatCategory::class,
    ];

    /** @var class-string */
    private string $model;

    /** @var array<int, int>|null */
    private ?array $only = null;

    private ?int $parentWhatCategoryId = null;

    private function __construct(private string $type)
    {
        if (! isset(self::MODELS[$type])) {
            throw new InvalidArgumentException("unknown category type: $type");
        }

        $this->model = self::MODELS[$type];
    }

    public static function what(): self
    {
        return new self('what');
    }

    public static function who(): self
    {
        return new self('who');
    }

    public static function subWhat(): self
    {
        return new self('sub_what');
    }

    /**
     * Restrict to a fixed set of ids. Several admin forms offer only a subset,
     * for example InitiativesController::create() with [1, 3, 4, 8, 18].
     *
     * @param array<int, int> $ids
     */
    public function only(array $ids): self
    {
        $this->only = $ids;

        return $this;
    }

    /** Sub-categories belonging to one what-category. */
    public function under(int $whatCategoryId): self
    {
        if ($this->type !== 'sub_what') {
            throw new InvalidArgumentException('under() applies to sub-categories only');
        }

        $this->parentWhatCategoryId = $whatCategoryId;

        return $this;
    }

    public function builder(): Builder
    {
        $query = $this->model::query();

        if ($this->only !== null) {
            $query->whereIn('id', $this->only);
        }

        if ($this->parentWhatCategoryId !== null) {
            $query->where('what_category_id', $this->parentWhatCategoryId);
        }

        return $query->orderBy('id');
    }

    public function get(): Collection
    {
        return $this->builder()->get();
    }

    /** id => name, for a select box. */
    public function options(): \Illuminate\Support\Collection
    {
        return $this->builder()->pluck('name', 'id');
    }

    public function nameFor(int $id): ?string
    {
        return $this->model::query()->whereKey($id)->value('name');
    }

    /**
     * The category name attached to an initiative. Replaces a raw correlated
     * subquery that interpolated the initiative id directly into SQL.
     */
    public function nameForInitiative(int $initiativeId): ?string
    {
        $column = match ($this->type) {
            'what'     => 'what_category_id',
            'who'      => 'who_category_id',
            'sub_what' => 'sub_what_category_id',
        };

        $categoryId = Initiative::query()->whereKey($initiativeId)->value($column);

        return $categoryId === null ? null : $this->nameFor((int) $categoryId);
    }
}
