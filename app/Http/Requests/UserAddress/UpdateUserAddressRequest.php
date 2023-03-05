<?php

namespace App\Http\Requests\UserAddress;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address_line' => 'string|min:3',
            'city' => 'string|min:3',
            'postal_code' => 'string|min:2',
            'country' => 'string|min:2',
            'active' => 'boolean'
        ];
    }
}