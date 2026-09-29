<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'title' => [$presence, 'required', 'string', 'max:255'],
            'body' => [$presence, 'required', 'string', 'max:60000'],
            'user_id' => ['prohibited']
        ];
    }
}
