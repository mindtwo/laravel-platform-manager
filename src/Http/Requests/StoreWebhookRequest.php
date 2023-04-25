<?php

namespace mindtwo\LaravelPlatformManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use mindtwo\LaravelPlatformManager\Validation\ValidateWebhookData;
use mindtwo\LaravelPlatformManager\Validation\ValidateWebhookName;

class StoreWebhookRequest extends FormRequest
{
    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = true;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'hook' => 'required|string|max:255',
            'data' => 'required|json',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            (new ValidateWebhookName)($this->validated('hook'));
            (new ValidateWebhookData)($validator, $this->validated('hook'), $this->validated('data'));
        });
    }
}
