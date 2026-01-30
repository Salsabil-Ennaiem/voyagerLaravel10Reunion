<?php

namespace App\Http\Requests\Organistion;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganisationRequest extends FormRequest
{


    public function rules()
    {
        return [
            'nom' => 'required|string|max:150',
            'email_contact' => 'nullable|email|max:255',
            'adresse' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'chef_organisation_id' => 'required|exists:users,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'required|boolean',
        ];
    }
}
