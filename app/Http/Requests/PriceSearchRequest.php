<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PriceSearchRequest extends FormRequest
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
            'codigoItemCatalogo' => ['required_without:codigoServico', 'integer', 'min:1'],
            'codigoServico' => ['required_without:codigoItemCatalogo', 'integer', 'min:1'],
            'codigoUasg' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', 'string', 'size:2'],
            'codigoMunicipio' => ['nullable', 'integer', 'min:1'],
            'dataResultado' => ['nullable', 'boolean'],
            'codigoClasse' => ['nullable', 'integer', 'min:1'],
            'poder' => ['nullable', 'string', 'max:32'],
            'esfera' => ['nullable', 'string', 'max:32'],
            'idCompra' => ['nullable', 'string', 'max:32'],
            'dataCompraInicio' => ['nullable', 'date_format:Y-m-d'],
            'dataCompraFim' => ['nullable', 'date_format:Y-m-d'],
            'descricao' => ['nullable', 'string'],
        ];
    }
}