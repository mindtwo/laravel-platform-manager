<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class ExternalPlatform extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = \mindtwo\LaravelPlatformManager\Models\ExternalPlatform::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make(__('Name'), 'name')->rules(['required']),
            Text::make(__('Hostname'), 'hostname')->rules(['required']),
            Text::make(__('Webhook Path'), 'webhook_path')->rules(['required']),
            Text::make(__('Web Hook Auth Token'), 'webhook_auth_token')->rules(['required']),
        ];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return trans_choice('External Platform', 2);
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return trans_choice('External Platform', 1);
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
