<?php

namespace mindtwo\LaravelPlatformManager\Nova;

use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Markdown;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class WebhookDispatchV2 extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = \mindtwo\LaravelPlatformManager\Models\V2\WebhookDispatch::class;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'hook',
        'ulid',
    ];

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

            Text::make(__('Url'), 'url')->readonly(),
            Number::make(__('Status'), 'status')->readonly(),

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

            DateTime::make(__('Created at'), 'created_at')->readonly()->sortable(),
        ];
    }

    /**
     * Get the displayable label of the resource.
     */
    public static function label(): string
    {
        return trans_choice('Webhook Dispatches', 2).' (V2)';
    }

    /**
     * Get the displayable singular label of the resource.
     */
    public static function singularLabel(): string
    {
        return trans_choice('Webhook Dispatch', 1).' (V2)';
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
