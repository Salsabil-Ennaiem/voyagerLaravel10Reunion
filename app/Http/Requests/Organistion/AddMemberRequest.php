<?php

namespace App\Http\Requests\Organistion;

use Illuminate\Foundation\Http\FormRequest;

class AddMemberRequest extends FormRequest
{
    

    public function rules()
    {
        return [
            'email' => 'required|email',
            'fonction' => 'nullable|string|max:255',
        ];
    }
}
