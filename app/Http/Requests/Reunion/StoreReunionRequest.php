<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class StoreReunionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handled by Policy/Controller
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
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'required|in:brouillon,planifiee,en_cours,terminee,annulee',
            'type' => 'required|in:presentiel,visio,hybride',
            'participants' => 'nullable|array',
            'participants.*' => 'email',
            'organisation_id' => 'required|exists:organisations,id',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'participants.*.email' => 'Chaque participant doit avoir une adresse email valide.',
            'participants.required' => 'Au moins un participant est requis.',
            'organisation_id.required' => 'L\'organisation est requise.',
            'organisation_id.exists' => 'L\'organisation sélectionnée n\'existe pas.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure description/lieu are null if empty
        $this->merge([
            'description' => $this->description ?: null,
            'lieu' => $this->lieu ?: null,
        ]);

        // If organisation_id is missing, try to get from user session
        if (!$this->organisation_id && $this->user()) {
            $orgId = $this->user()->getActiveOrganisationId();
            if ($orgId) {
                $this->merge(['organisation_id' => $orgId]);
            }
        }
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();
                $isAdmin = $user->isAdmin();
                
                // Restriction: Le chef ne peut pas ajouter une réunion pour une date passée
                if (!$isAdmin && $this->date_debut) {
                   $start = Carbon::parse($this->date_debut);
                   if ($start->isPast()) {
                       $validator->errors()->add(
                           'date_debut',
                           'Un Chef ne peut pas créer de réunion dans le passé.'
                       );
                   }
                }

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

                // Authorization: User must have rights on the organisation
                if ($this->organisation_id) {
                    if (!$isAdmin && !$user->isChefOf((int) $this->organisation_id)) {
                        $validator->errors()->add(
                            'organisation_id',
                            'Vous n\'avez pas les droits pour créer une réunion dans cette organisation.'
                        );
                    }
                }
            }
        ];
    }
}
