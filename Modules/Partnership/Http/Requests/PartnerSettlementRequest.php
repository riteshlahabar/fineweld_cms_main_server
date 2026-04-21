<?php

namespace Modules\Partnership\Http\Requests;

use App\Traits\FormatsDateInputs;
use Illuminate\Foundation\Http\FormRequest;

class PartnerSettlementRequest extends FormRequest
{
    use FormatsDateInputs;

    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $rulesArray = [
            'settlement_date' => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
            'prefix_code' => ['nullable', 'string', 'max:250'],
            'settlement_code' => ['required', 'string', 'max:50'],
            'count_id' => ['required', 'numeric'],
            'reference_no' => ['nullable', 'string', 'max:50'],
            'payment_type_id' => 'required|integer',
            'amount' => 'required|numeric|gt:0',
            'payment_direction' => 'required|string|in:paid,received',
            'partner_id' => 'required|integer|exists:partners,id',
        ];

        // For Update Operation
        if ($this->isMethod('PUT')) {
            $rulesArray['settlement_id'] = ['required'];
        }

        return $rulesArray;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        /**
         * @method formatDateInput
         * Defined in Trait FormatsDateInputs
         * */
        $contractDate = $this->input('settlement_date');

        $this->merge([
            'settlement_date' => $this->toSystemDateFormat($contractDate),
            'settlement_code' => $this->getSaleCode(),
        ]);
    }

    /**
     * @return string
     */
    protected function getSaleCode()
    {
        $prefixCode = $this->input('prefix_code');
        $countId = $this->input('count_id');

        return $prefixCode.$countId;
    }

    public function messages(): array
    {
        $responseMessages = [];

        if ($this->isMethod('PUT')) {
            $responseMessages['id.required'] = 'ID Not found to update record';
        }

        return $responseMessages;
    }
}
