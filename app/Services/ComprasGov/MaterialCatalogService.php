<?php

namespace App\Services\ComprasGov;

class MaterialCatalogService
{
    public function __construct(private readonly ComprasGovApiClient $client)
    {
    }

    private array $localFallbackItems = [
        [
            "codigoGrupo" => 71, "nomeGrupo" => "MOBILIÁRIOS",
            "codigoClasse" => 7110, "nomeClasse" => "MOBILIÁRIO PARA ESCRITÓRIO",
            "codigoPdm" => 313, "nomePdm" => "CADEIRA ESCRITÓRIO",
            "codigoItem" => 206504,
            "descricaoItem" => "CADEIRA ESCRITÓRIO, MATERIAL ESTRUTURA: TUBO AÇO , MATERIAL REVESTIMENTO ASSENTO E ENCOSTO: COURO , MATERIAL ENCOSTO: ESPUMA INJETADA , MATERIAL ASSENTO: ESPUMA LAMINADA , TRATAMENTO SUPERFICIAL ESTRUTURA: NIQUELADO , TIPO BASE: GIRATÓRIO , TIPO ENCOSTO: BAIXO , APOIO BRAÇO: COM BRAÇOS , REGULAGEM VERTICAL: COM REGULAGEM , COR: AMARELA ",
            "statusItem" => true, "itemSustentavel" => false
        ],
        [
            "codigoGrupo" => 71, "nomeGrupo" => "MOBILIÁRIOS",
            "codigoClasse" => 7110, "nomeClasse" => "MOBILIÁRIO PARA ESCRITÓRIO",
            "codigoPdm" => 313, "nomePdm" => "CADEIRA ESCRITÓRIO",
            "codigoItem" => 243756,
            "descricaoItem" => "CADEIRA ESCRITÓRIO, MATERIAL REVESTIMENTO ASSENTO E ENCOSTO: TECIDO 100% LÃ , MATERIAL ASSENTO: ESPUMA POLIURETANO INJETADO , TIPO BASE: FIXO , TIPO ENCOSTO: MÉDIO , APOIO BRAÇO: COM BRAÇOS , REGULAGEM VERTICAL: SEM REGULAGEM , COR: AZUL , ACABAMENTO SUPERFICIAL ESTRUTURA: PINTURA , COR ESTRUTURA: PRETA ",
            "statusItem" => true, "itemSustentavel" => false
        ],
        [
            "codigoGrupo" => 75, "nomeGrupo" => "MATERIAL DE ESCRITÓRIO E PAPELARIA",
            "codigoClasse" => 7530, "nomeClasse" => "ARTIGOS DE PAPELARIA E DE ESCRITÓRIO",
            "codigoPdm" => 17351, "nomePdm" => "PAPEL SULFITE",
            "codigoItem" => 150032,
            "descricaoItem" => "PAPEL SULFITE, TIPO: ALCALINO, COR: BRANCO, FORMATO: A4, GRAMATURA: 75 G/M2, APLICAÇÃO: IMPRESSÃO LASER, JATO DE TINTA E FOTOCOPIADORA",
            "statusItem" => true, "itemSustentavel" => true
        ],
        [
            "codigoGrupo" => 75, "nomeGrupo" => "MATERIAL DE ESCRITÓRIO E PAPELARIA",
            "codigoClasse" => 7530, "nomeClasse" => "ARTIGOS DE PAPELARIA E DE ESCRITÓRIO",
            "codigoPdm" => 17351, "nomePdm" => "PAPEL SULFITE",
            "codigoItem" => 213456,
            "descricaoItem" => "PAPEL SULFITE, TIPO: RECICLADO, COR: PARDO, FORMATO: A4, GRAMATURA: 75 G/M2",
            "statusItem" => true, "itemSustentavel" => true
        ],
        [
            "codigoGrupo" => 75, "nomeGrupo" => "MATERIAL DE ESCRITÓRIO E PAPELARIA",
            "codigoClasse" => 7520, "nomeClasse" => "MATERIAIS E ACESSÓRIOS DE ESCRITÓRIO",
            "codigoPdm" => 1845, "nomePdm" => "CANETA ESFEROGRÁFICA",
            "codigoItem" => 334120,
            "descricaoItem" => "CANETA ESFEROGRÁFICA, MATERIAL: PLÁSTICO TRANSPARENTE, TIPO TINTA: AZUL, TRAÇO: MÉDIO 1,0 MM",
            "statusItem" => true, "itemSustentavel" => false
        ],
        [
            "codigoGrupo" => 70, "nomeGrupo" => "INFORMÁTICA",
            "codigoClasse" => 7010, "nomeClasse" => "EQUIPAMENTOS DE PROCESSAMENTO DE DADOS",
            "codigoPdm" => 0411, "nomePdm" => "MICROCOMPUTADOR",
            "codigoItem" => 441029,
            "descricaoItem" => "MICROCOMPUTADOR, TIPO: DESKTOP, PROCESSADOR: CORE I7, MEMÓRIA RAM: 16 GB, ARMAZENAMENTO: SSD 512 GB",
            "statusItem" => true, "itemSustentavel" => false
        ],
        [
            "codigoGrupo" => 70, "nomeGrupo" => "INFORMÁTICA",
            "codigoClasse" => 7010, "nomeClasse" => "EQUIPAMENTOS DE PROCESSAMENTO DE DADOS",
            "codigoPdm" => 0412, "nomePdm" => "NOTEBOOK",
            "codigoItem" => 482110,
            "descricaoItem" => "COMPUTADOR PORTÁTIL (NOTEBOOK), TELA: 15.6 POL, PROCESSADOR: CORE I5, MEMÓRIA RAM: 8 GB, ARMAZENAMENTO: SSD 256 GB",
            "statusItem" => true, "itemSustentavel" => false
        ]
    ];

    public function searchItems(array $query = []): array
    {
        // Se há busca textual, faz a tradução de "Texto Livre" para "Código PDM"
        if (!empty($query['descricaoItem'])) {
            $searchTerm = mb_strtolower($query['descricaoItem'], 'UTF-8');
            
            // 1. Busca no banco de dados local sincronizado
            $pdm = \Illuminate\Support\Facades\DB::table('compras_pdms')
                ->where('nomePdm', 'ilike', '%' . $searchTerm . '%')
                ->first();

            // 2. Se achou uma categoria PDM compatível com o texto, ignora o texto quebrado
            // e pesquisa na API do governo usando o código PDM oficial!
            if ($pdm) {
                unset($query['descricaoItem']);
                $query['codigoPdm'] = $pdm->codigoPdm;
            }
        }

        // Se não encontrar PDM ou se não houver texto, segue o fluxo normal do governo
        return $this->client->get('/modulo-material/4_consultarItemMaterial', $query);
    }

    public function supplyUnits(array $query = []): array
    {
        return $this->client->get('/modulo-material/6_consultarMaterialUnidadeFornecimento', $query);
    }

    public function characteristics(array $query = []): array
    {
        return $this->client->get('/modulo-material/7_consultarMaterialCaracteristicas', $query);
    }
}