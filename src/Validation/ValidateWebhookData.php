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
    public function __invoke(Validator $validator, string $hook, string $data)
    {
        $rules = config("webhooks.$hook.rules");

        $dataValidator = FacadesValidator::make(
            json_decode($data, true),
            $rules,
        );

        if ($dataValidator->fails()) {
            $validator->errors()->merge($dataValidator->errors());
        }
    }
}
