<?php

namespace Modules\Partnership\Http\Requests;

use App\Traits\FormatsDateInputs;
use Illuminate\Foundation\Http\FormRequest;

class ContractRequest extends FormRequest
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
            'contract_date' => ['required', 'date_format:'.implode(',', $this->getDateFormats())],
            'prefix_code' => ['nullable', 'string', 'max:250'],
            'contract_code' => ['required', 'string', 'max:50'],
            'count_id' => ['required', 'numeric'],
            'reference_no' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:250'],
            'row_count' => ['required', 'integer', 'min:1'],
        ];

        // For Update Operation
        if ($this->isMethod('PUT')) {
            $rulesArray['contract_id'] = ['required'];
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
        $contractDate = $this->input('contract_date');

        $this->merge([
            'contract_date' => $this->toSystemDateFormat($contractDate),
            'contract_code' => $this->getSaleCode(),
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
        $responseMessages = [
            'row_count.min' => __('item.please_select_items'),
        ];

        if ($this->isMethod('PUT')) {
            $responseMessages['id.required'] = 'ID Not found to update record';
        }

        return $responseMessages;
    }
}
