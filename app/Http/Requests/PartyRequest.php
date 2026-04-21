<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartyRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partyType = $this->input('party_type', 'vendor');
        
        $rulesArray = [
            // FORM FIELDS (REQUIRED)
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['required', 'in:proprietor,partnership,private_limited,public_limited,one_person_company,limited_liability_partnership'],
            'vendor_type' => ['required', 'in:customer,supplier,both'],
            'primary_name' => ['required', 'string', 'max:100'],
            'primary_mobile' => ['required', 'string', 'max:20'],
            
            // OPTIONAL FIELDS
            'company_pan' => ['nullable', 'string', 'max:20'],
            'company_gst' => ['nullable', 'string', 'max:20'],
            'company_tan' => ['nullable', 'string', 'max:20'],
            'company_msme' => ['nullable', 'string', 'max:50'],
            'date_of_incorporation' => ['nullable', 'date'],
            'primary_email' => ['nullable', 'email', 'max:255'],
            'primary_whatsapp' => ['nullable', 'string', 'max:20'],
            'primary_dob' => ['nullable', 'date'],
            
            'secondary_name' => ['nullable', 'string', 'max:100'],
            'secondary_email' => ['nullable', 'email', 'max:255'],
            'secondary_mobile' => ['nullable', 'string', 'max:20'],
            'secondary_whatsapp' => ['nullable', 'string', 'max:20'],
            'secondary_dob' => ['nullable', 'date'],
            
            'company_address' => ['nullable', 'string'],
            'billing_address' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'micr_code' => ['nullable', 'string', 'max:20'],
            
            // File uploads
            'pan_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'tan_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'gst_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'msme_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'cancelled_cheque' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            
            // ✅ FIXED: NO STATUS VALIDATION - Controller handles it
            'currency_id' => ['sometimes', 'integer', 'exists:currencies,id'],
            'party_type' => ['sometimes', 'in:vendor'],
            'default_party' => ['sometimes', 'in:0,1'],
            'opening_balance' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'transaction_date' => ['sometimes', 'nullable', 'date'],
            'operation' => ['sometimes', 'in:save,update'],
        ];

        // Unique rules
        if ($this->isMethod('PUT')) {
            $partyId = $this->input('party_id');
            $rulesArray['party_id'] = ['required', 'exists:parties,id'];
            $rulesArray['primary_mobile'] = ['required', 'string', 'max:20', Rule::unique('parties')->where('party_type', $partyType)->ignore($partyId)];
            $rulesArray['primary_email'] = ['nullable', 'email', 'max:255', Rule::unique('parties')->where('party_type', $partyType)->ignore($partyId)];
            $rulesArray['company_gst'] = ['nullable', 'string', 'max:20', Rule::unique('parties')->where('party_type', $partyType)->ignore($partyId)];
            $rulesArray['company_pan'] = ['nullable', 'string', 'max:20', Rule::unique('parties')->where('party_type', $partyType)->ignore($partyId)];
        } else {
            $rulesArray['primary_mobile'] = ['required', 'string', 'max:20', Rule::unique('parties', 'primary_mobile')->where('party_type', $partyType)];
            $rulesArray['primary_email'] = ['nullable', 'email', 'max:255', Rule::unique('parties', 'primary_email')->where('party_type', $partyType)];
            $rulesArray['company_gst'] = ['nullable', 'string', 'max:20', Rule::unique('parties', 'company_gst')->where('party_type', $partyType)];
            $rulesArray['company_pan'] = ['nullable', 'string', 'max:20', Rule::unique('parties', 'company_pan')->where('party_type', $partyType)];
        }

        return $rulesArray;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'party_type' => 'vendor',
            // ✅ REMOVED status from here - controller handles it
            'currency_id' => 1,
            'default_party' => $this->has('default_party') ? 1 : 0,
            'opening_balance' => 0,
            'transaction_date' => now()->format('Y-m-d'),
            'is_set_credit_limit' => 0,
            'credit_limit' => 0,
            'operation' => $this->operation ?? 'save',
        ]);
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required',
            'company_type.required' => 'Please select company type',
            'vendor_type.required' => 'Please select vendor type',
            'primary_name.required' => 'Primary contact name is required',
            'primary_mobile.required' => 'Primary mobile number is required',
            'primary_mobile.unique' => 'This mobile number is already registered',
            'company_gst.unique' => 'This GST number is already registered',
            'company_pan.unique' => 'This PAN is already registered',
        ];
    }
}
