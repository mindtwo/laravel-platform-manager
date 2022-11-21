<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use mindtwo\LaravelPlatformManager\Enums\PlatformVisibility;

class Platform extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = mindtwo\LaravelPlatformManager\Models\Platform::class;

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
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Boolean::make(__('Main'), 'is_main'),

            Select::make(__('Visibility'), 'visibility')
                ->options(PlatformVisibility::asSelectArray())
                ->displayUsingLabels()
                ->rules(['required']),

            Text::make(__('Name'), 'name')->sortable()->rules(['required', 'max:255']),
            Text::make(__('Email'), 'email')->sortable()->rules(['required', 'max:255']),
            Text::make(__('Hostname'), 'hostname')->sortable()->rules(['max:255'])->help(
                __('Multiple entries can be separated by commas.')
            ),

            Image::make(__('Platform Logo'), 'logo_file')->disk(config('media-library.disk_name'))->hideFromIndex(),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return trans_choice('Platforms', 2);
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return trans_choice('Platforms', 1);
    }
}
