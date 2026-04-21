<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CatalogSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pagina' => ['nullable', 'integer', 'min:1'],
            'tamanhoPagina' => ['nullable', 'integer', 'min:10', 'max:500'],
            'codigoItem' => ['nullable', 'integer', 'min:1'],
            'codigoGrupo' => ['nullable', 'integer', 'min:1'],
            'codigoClasse' => ['nullable', 'integer', 'min:1'],
            'codigoPdm' => ['nullable', 'integer', 'min:1'],
            'codigoSecao' => ['nullable', 'integer', 'min:1'],
            'codigoDivisao' => ['nullable', 'integer', 'min:1'],
            'codigoSubclasse' => ['nullable', 'integer', 'min:1'],
            'codigoCpc' => ['nullable', 'integer', 'min:1'],
            'codigoServico' => ['nullable', 'integer', 'min:1'],
            'descricaoItem' => ['nullable', 'string', 'max:255'],
            'statusItem' => ['nullable', 'boolean'],
            'statusGrupo' => ['nullable', 'boolean'],
            'statusClasse' => ['nullable', 'boolean'],
            'statusPdm' => ['nullable', 'boolean'],
            'statusSecao' => ['nullable', 'boolean'],
            'statusDivisao' => ['nullable', 'boolean'],
            'statusSubclasse' => ['nullable', 'boolean'],
            'statusServico' => ['nullable', 'boolean'],
            'statusUnidadeFornecimentoPdm' => ['nullable', 'boolean'],
            'statusUnidadeMedida' => ['nullable', 'boolean'],
            'exclusivoCentralCompras' => ['nullable', 'boolean'],
            'bps' => ['nullable', 'boolean'],
        ];
    }
}