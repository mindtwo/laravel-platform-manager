<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class WebhookRequestV2 extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = \mindtwo\LaravelPlatformManager\Models\V2\WebhookRequest::class;

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make(__('Id'), 'ulid')->readonly(),
            Text::make(__('Hook'), 'hook')->readonly(),

            Text::make(__('Requested from'), 'requested_from')->readonly(),
            Number::make(__('Response url'), 'response_url')->readonly(),

            Markdown::make(__('Request payload'), 'payload')
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

            Date::make(__('Created at'), 'created_at')->readonly(),
        ];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return trans_choice('Webhook Requests', 2).' (V2)';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return trans_choice('Webhook Request', 1).' (V2)';
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
