<?php

namespace mindtwo\LaravelPlatformManager\Nova\Filters;

use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class PlatformFilter extends Filter
{
    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  NovaRequest  $request
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function apply(NovaRequest $request, $query, $value): Builder
    {
        return $query->where('platform_id', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  NovaRequest  $request
     * @return array
     */
    public function options(NovaRequest $request): array
    {
        return config('platform-resolver.model')::all()->mapWithKeys(function ($model) {
            return [$model->name => $model->id];
        })->toArray();
    }
}
