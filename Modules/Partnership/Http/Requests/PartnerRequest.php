<?php

namespace Modules\Partnership\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerRequest extends FormRequest
{
    /**
     * Indicates if the validator should stop on the first rule failure.
     */
    protected $stopOnFirstFailure = true;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // $partnerType = $this->input('partner_type', 'individual');

        $rulesArray = [
            // 'partner_type'              => ['required', 'string', 'in:individual,business,organization'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'tax_type' => ['nullable', 'string', 'in:unregistered,registered'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            // 'currency_id'               => ['required', 'exists:currencies,id'],
            'status' => ['required', 'boolean'],
            // 'default_partner'           => ['nullable', 'boolean'],
            'state_id' => ['nullable', 'exists:states,id'],
        ];

        if ($this->isMethod('PUT')) {
            $partnerId = $this->input('partner_id');
            $rulesArray['partner_id'] = ['required', 'exists:partners,id'];

            $rulesArray['mobile'] = ['nullable', 'string', 'max:20', Rule::unique('partners')->ignore($partnerId)];
            $rulesArray['phone'] = ['nullable', 'string', 'max:20', Rule::unique('partners')->ignore($partnerId)];
            $rulesArray['whatsapp'] = ['nullable', 'string', 'max:20', Rule::unique('partners')->ignore($partnerId)];
            $rulesArray['email'] = ['nullable', 'email', 'max:100', Rule::unique('partners')->ignore($partnerId)];
        } else {
            $rulesArray['mobile'] = ['nullable', 'string', 'max:20', Rule::unique('partners')];
            $rulesArray['phone'] = ['nullable', 'string', 'max:20', Rule::unique('partners')];
            $rulesArray['whatsapp'] = ['nullable', 'string', 'max:20', Rule::unique('partners')];
            $rulesArray['email'] = ['nullable', 'email', 'max:100', Rule::unique('partners')];
        }

        return $rulesArray;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // $this->merge([
        //     'default_partner'       => $this->has('default_partner') ? 1 : 0,
        // ]);
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        $responseMessages = [
            'first_name.required' => 'First name is required',
            'first_name.max' => 'First name cannot exceed 100 characters',
            'last_name.max' => 'Last name cannot exceed 100 characters',

            'status.required' => 'Status is required',

        ];

        if ($this->isMethod('PUT')) {
            $responseMessages['partner_id.required'] = 'Partner ID is required for update';
            $responseMessages['partner_id.exists'] = 'Partner not found';
        }

        return $responseMessages;
    }
}
