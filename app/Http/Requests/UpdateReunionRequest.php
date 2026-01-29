<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class UpdateReunionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'objet' => 'required|string|max:200',
            'description' => 'nullable|string',
            'lieu' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'statut' => 'required|in:brouillon,planifiee,en_cours,terminee,annulee',
            'type' => 'required|in:presentiel,visio,hybride',
            'participants' => 'nullable|array',
            'participants.*' => 'email',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'description' => $this->description ?: null,
            'lieu' => $this->lieu ?: null,
        ]);
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Validation: date_debut and date_fin must be on the same day
                if ($this->date_debut && $this->date_fin) {
                    $start = Carbon::parse($this->date_debut);
                    $end = Carbon::parse($this->date_fin);
                    
                    if (!$start->isSameDay($end)) {
                        $validator->errors()->add(
                            'date_fin',
                            'La date de fin doit être le même jour que la date de début.'
                        );
                    }
                }
            }
        ];
    }
}

