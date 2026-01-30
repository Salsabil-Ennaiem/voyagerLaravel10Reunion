<?php

namespace App\Http\Requests\Organistion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
  

    public function rules()
    {
        return [
            'fonction' => 'nullable|string|max:255',
        ];
    }
}
