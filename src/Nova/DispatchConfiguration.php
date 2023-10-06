<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;
use mindtwo\LaravelPlatformManager\Models\DispatchConfiguration as DispatchConfigurationModel;
use mindtwo\LaravelPlatformManager\Nova\Filters\PlatformFilter;

abstract class DispatchConfiguration extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = DispatchConfigurationModel::class;

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

            Text::make(__('Name'), 'hook')->sortable()->rules(['required']),
            Text::make(__('Description'), 'description'),

            Text::make(__('Endpoint'), 'url')
                ->rules(['required', 'max:255'])
                ->fillUsing(
                    fn ($request, $model, $attribute, $requestAttribute) => $model->{$attribute} =
                        Str::of($request->input($attribute))
                        ->whenEndsWith('/', fn ($str) => Str::of(substr($str, 0, -1)))
                        ->when(fn ($str) => !str_starts_with($str, '/'), fn ($str) => Str::of("/$str"))
                        ->toString()
                ),
            Text::make(__('Auth Token'), 'auth_token')->rules(['required', 'max:255']),

            BelongsTo::make(trans_choice('Platforms', 1), 'platform', $this->getPlatformNovaResource())->sortable()->nullable(),

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
        return trans_choice('Dispatch Configurations', 2);
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return trans_choice('Dispatch Configuration', 1);
    }

    /**
     * Get the logical group associated with the resource.
     *
     * @return string
     */
    public static function group()
    {
        return trans_choice('Platforms', 1);
    }
}
