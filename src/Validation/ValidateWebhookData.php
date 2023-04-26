<?php

namespace mindtwo\LaravelPlatformManager\Validation;

use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\Validator;

class ValidateWebhookData
{
    /**
     * Validate received data.
     *
     * @return void
     */
    public function __invoke(Validator $validator, string $hook, array $data)
    {
        $rules = config("webhooks.$hook.rules");

        $dataValidator = FacadesValidator::make(
            $data,
            $rules,
        );

        if ($dataValidator->fails()) {
            $validator->errors()->merge($dataValidator->errors());
        }
    }
}
