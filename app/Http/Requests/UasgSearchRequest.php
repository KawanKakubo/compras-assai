<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UasgSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pagina' => ['nullable', 'integer', 'min:1'],
            'codigoUasg' => ['nullable', 'string', 'max:20'],
            'usoSisg' => ['nullable', 'boolean'],
            'cnpjCpfOrgao' => ['nullable', 'string', 'max:20'],
            'cnpjCpfOrgaoVinculado' => ['nullable', 'string', 'max:20'],
            'cnpjCpfOrgaoSuperior' => ['nullable', 'string', 'max:20'],
            'siglaUf' => ['nullable', 'string', 'size:2'],
            'statusUasg' => ['required', 'boolean'],
        ];
    }
}