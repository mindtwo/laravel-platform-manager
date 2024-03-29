<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use mindtwo\LaravelPlatformManager\Models\Platform as PlatformModel;

/**
 * @template TModel of PlatformModel
 *
 * @mixin TModel
 */
class Platform extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = PlatformModel::class;

    /**
     * Indicates if the resource should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name',
    ];

    /**
     * Default sorting column
     */
    public static string $defaultSort = 'id';

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return $this->getBaseFields();
    }

    /**
     * @return array<int, Field>
     */
    public function getBaseFields()
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            Boolean::make(__('Active'), 'is_active')
                ->help(__('Toggle the platforms state to active/inactive. Inactive platforms are not used for matching.')),

            Boolean::make(__('Main'), 'is_main')
                ->help(__('Main platforms are used as fallback when no other platform can be matched via authtoken or hostname.')),

            Boolean::make(__('Headless'), 'is_headless')
                ->help(__('Headless platforms platforms without a frontend provided by this app. Headless platforms may be consumed by other apps or frontends via API.')),

            Text::make(__('Name'), 'name')->sortable()->rules(['required', 'max:255']),

            Text::make(__('Hostname'), 'hostname')
                ->sortable()
                ->rules(['max:255'])
                ->fillUsing(
                    fn ($request, $model, $attribute, $requestAttribute) => $model->{$attribute} =
                        Str::of($request->input($attribute))
                            ->replaceFirst('http://', '')
                            ->replaceFirst('https://', '')
                            ->before('/')
                            ->toString()
                ),

            Text::make(__('Additional Hostnames'), 'additional_hostnames')
                ->help(__('Multiple entries can be separated by commas.'))
                ->hideFromIndex()
                ->resolveUsing(fn ($item) => collect($item ?? [])->implode(',')) // @phpstan-ignore-line
                ->fillUsing(
                    fn ($request, $model, $attribute, $requestAttribute) => $model->{$attribute} =
                    collect(explode(',', $request->input($attribute) ?? ''))
                        ->map(
                            fn ($str) => Str::of($str)
                                ->replaceFirst('http://', '')
                                ->replaceFirst('https://', '')
                                ->before('/')
                                ->toString()
                        )
                        ->toArray()
                ),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return __('Platforms');
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return __('Platform');
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

    /**
     * Get a fresh instance of the model represented by the resource.
     *
     * @return PlatformModel
     */
    public static function newModel()
    {
        /** @var TModel $model */
        $model = static::$model;

        return new $model;
    }
}
