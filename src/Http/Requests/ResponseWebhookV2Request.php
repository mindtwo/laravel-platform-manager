<?php

namespace mindtwo\LaravelPlatformManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponseWebhookV2Request extends FormRequest
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
            'ulid' => 'required|string|max:255',
            'result' => 'sometimes|array',
        ];
    }
}
