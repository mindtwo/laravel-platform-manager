<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;
use mindtwo\LaravelPlatformManager\Enums\AuthTokenTypeEnum;
use mindtwo\LaravelPlatformManager\Models\AuthToken as AuthTokenModel;
use mindtwo\LaravelPlatformManager\Nova\Filters\PlatformFilter;

abstract class AuthToken extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = AuthTokenModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'description';

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
        'description',
    ];

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make(__('Platform'), 'platform', $this->getPlatformNovaResource())->sortable()->rules(['required']),
            Select::make(__('Type'), 'type')
                ->options(AuthTokenTypeEnum::asSelectArray())
                ->displayUsingLabels()->sortable(),
            Text::make(__('Description'), 'description')->sortable()->rules(['required', 'max:255']),

            new Panel(__('Readonly (auto populated fields)'), [
                BelongsTo::make(__('User'), 'user', $this->getUserNovaResource())->nullable()->sortable()->readonly()->hideFromIndex(),
                Text::make(__('Token'), 'token')->sortable()->readonly()->hideFromIndex(),
            ]),
        ];
    }

    abstract public function getUserNovaResource(): string;

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
        return __('Auth Tokens');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('Auth Token');
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
