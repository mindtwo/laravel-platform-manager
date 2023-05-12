<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use mindtwo\LaravelPlatformManager\Enums\WebhookTypeEnum;

class WebhookRequest extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = \mindtwo\LaravelPlatformManager\Models\WebhookRequest::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make(__('Hook'), 'hook')->readonly(),
            Text::make(__('Type'), 'type')->resolveUsing(function ($name) {
                return $name === WebhookTypeEnum::Incoming->value ? __('Incoming') : __('Outgoing');
            })->readonly(),

            Text::make(__('Url'), 'url')->readonly(),
            Number::make(__('Status'), 'status')->readonly(),

            Markdown::make(__('Request Data'), 'request')
                ->readonly()
                ->resolveUsing(function ($value) {
                    try {
                        if (is_string($value)) {
                            $value = json_decode($value, true);
                        }

                        $json = json_encode($value, JSON_PRETTY_PRINT);

                        return "```json\n$json\n```";
                    } catch (\Throwable $th) {
                        return $value;
                    }
                })
                ->hideFromIndex(),

            Markdown::make(__('Response Data'), 'response')
                ->readonly()
                ->resolveUsing(function ($value) {
                    try {
                        if (is_string($value)) {
                            $value = json_decode($value, true);
                        }

                        $json = json_encode($value, JSON_PRETTY_PRINT);

                        return "```json\n$json\n```";
                    } catch (\Throwable $th) {
                        return $value;
                    }
                })
                ->hideFromIndex(),
        ];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return trans_choice('Webhook Requests', 2);
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return trans_choice('Webhook Request', 1);
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
