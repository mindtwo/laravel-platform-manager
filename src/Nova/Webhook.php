<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Resource;
use mindtwo\LaravelPlatformManager\Models\Webhook as WebhookModel;
use mindtwo\LaravelPlatformManager\Nova\Filters\PlatformFilter;

abstract class Webhook extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = WebhookModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'hook';

    /**
     * Indicates if the resource should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'hook',
    ];

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            Boolean::make(__('Active'), 'active'),
            Select::make(__('Name'), 'hook')->sortable()->options(collect(array_keys(config('webhooks')))->toArray())->rules(['required', 'max:255']),

            BelongsTo::make(__('Platform'), 'platform', $this->getPlatformNovaResource())->sortable()->rules(['required']),
        ];
    }

    public function getPlatformNovaResource(): string
    {
        return Platform::class;
    }

    /**
     * Get the cards available for the request.
     */
    public function cards(Request $request): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        return [
            new PlatformFilter,
        ];
    }

    /**
     * Get the lenses available for the resource.
     */
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     */
    public function actions(Request $request): array
    {
        return [];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('Webhooks (v1)');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('Webhook (v1)');
    }

    /**
     * Get the logical group associated with the resource.
     *
     * @return string
     */
    public static function group()
    {
        return __('Platform');
    }
}
