<?php

namespace App\Http\Requests\Organistion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganisationRequest extends FormRequest
{
   
    public function rules()
    {
        $rules = [
            'nom' => 'required|string|max:255',
            'email_contact' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Only Admin can change the chef
        if ($this->user()->isAdmin()) {
            $rules['chef_organisation_id'] = 'nullable|exists:users,id';
        }

        return $rules;
    }
}
