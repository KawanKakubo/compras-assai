<?php

namespace App\Services\ComprasGov;

class PdmDictionaryService
{
    /**
     * Mapeia termos comuns para códigos PDM oficiais do governo.
     */
    private array $dictionary = [
        'carro' => 2954, // Veículo de representação / passeio
        'uno' => 2954,
        'gol' => 2954,
        'veiculo' => 2954,
        'camionete' => 2955, // Caminhão / Caminhonete
        'notebook' => 412,
        'computador' => 411,
        'pc' => 411,
        'impressora' => 505,
        'remedio' => 2001,
        'medicamento' => 2001,
        'dipirona' => 2001,
        'paracetamol' => 2002,
        'cimento' => 3001,
        'areia' => 3002,
        'caneta' => 1845,
        'papel' => 17351,
        'sulfite' => 17351,
    ];

    public function findPdmByTerm(string $term): ?int
    {
        $term = mb_strtolower($term);
        
        foreach ($this->dictionary as $keyword => $pdm) {
            if (str_contains($term, $keyword)) {
                return $pdm;
            }
        }

        return null;
    }
}
