
Skip to last reply
Skip to top
Skip to main content
GestGov
Como usar a API de Dados Abertos para identificar contratações de um CATMAT específico?
NELCA

    comprasnet
    api

Como usar a API de Dados Abertos para identificar contratações de um CATMAT específico?
NELCA

    comprasnet
    api

Mar 2025
May 2025
post by Monika on Mar 31, 2025
Monika
Mar 2025

Caros colegas, boa tarde!

Estou auxiliando um pesquisador da nossa unidade no levantamento de dados sobre compras governamentais de um determinado medicamento (CATMAT 443435). Ele havia iniciado o levantamento através de consulta de Atas SRP, pelo sistema de gestão de atas antigo. Esbarrando em alguns conceitos, ele nos procurou para ajudarmos.
Sugeri a consulta através das bases de dados abertos de compras governamentais porque conseguiria dados de compras por outras modalidades e das atas não visíveis no sistema de gestão SRP antigo (da nova lei, que a gente consegue consultar de forma mais completa com acesso por senha).

Indiquei a busca pelo link Swagger UI .

Porém não entendemos muito de programação e como ordenar os dados de maneira mais pedagógica.

Acredito que devemos utilizar os módulos:

/modulo-legado/6_consultarCompraItensSemLicitacao

/modulo-legado/4_consultarItensPregoes

/modulo-contratacoes/2_consultarItensContratacoes_PNCP_14133

/modulo-arp/2_consultarARPItem

Seria isso?

Se colocarmos apenas o ano e o CATMAT buscado, por exemplo, para itens de compra sem licitação (legado), ele me retorna a Request URL:

https://dadosabertos.compras.gov.br/modulo-legado/6_consultarCompraItensSemLicitacao?pagina=1&tamanhoPagina=10&dt_ano_aviso_licitacao=2020&co_conjunto_materiais=443435

e um Response body para download, que posso salvar em xlxs e gerar uma planilha. Mas ela vem toda desconfigurada, dificultando a separação das compras e compreensão dos dados.

Alguém conseguiria me dar um auxilio sobre o melhor modo de formatar isso e se esse caminho é mesmo a melhor forma de coletar dados para essa pesquisa? Ele precisará usar dados de acesso público.

Muito obrigada novamente por todo auxilio.

Monika Marins
Instituto de Bioquímica Médica Leopoldo de Meis/UFRJ

post by DiegoFGarcia on Mar 31, 2025
DiegoFGarcia
Mar 2025

@Monika,

O arquivo que você baixa no Swagger UI vem no forma JSON. Você precisa converter para CSV, antes de abrir como planilha. Essa ferramenta pode te ajudar: JSON To CSV Converter. É bem intuitiva.

Se for uma demanda institucional, você também pode entrar em contato com a Área de TI, se houver. Pedir para criarem um script que baixa os arquivos, converte e salva em uma pasta compartilhada (ETL), por exemplo.

post by Monika on Apr 1, 2025
Monika
Apr 2025

@DiegoFGarcia , Valiosa sua dica!
Já testei a ferramenta e auxiliou bastante na organização dos dados.

Muito obrigada!

post by marcelo.meira on Apr 1, 2025
marcelo.meira
Apr 2025

@Monika indico esse código. Na página há orientações suficientes para que o usuário possa executar a consulta, sem a necessidade de ser um programador.
Empresa Brasileira de Serviços Hospitalares
Planilha automatizada para Pesquisa de Preços - P3

post by FranklinBrasil on Apr 2, 2025
post by Monika on Apr 2, 2025
post by Monika on Apr 2, 2025
post by FranklinBrasil on Apr 2, 2025
post by DiegoFGarcia on Apr 2, 2025
post by FranklinBrasil on Apr 2, 2025
post by DiegoFGarcia on Apr 2, 2025
post by FranklinBrasil on Apr 3, 2025
10 days later
post by Fernando_Fernandes on Apr 13, 2025
2 months later
post by DiegoFGarcia on May 31, 2025

Related topics
Topic list, column headers with buttons are sortable.
Topic 	Replies 	Views 	Activity
API de busca de licitações públicas
NELCA
	8 	305 	Mar 19
Atas Nelca - Todas as atas SRP do Comprasnet
NELCA
	72 	9.7k 	Jul 2025
APIS e dados atualizados
Consultas Gerenciais
	5 	1.8k 	Sep 2023
Apresentação da API de Compras Governamentais #DadosAbertos
TIC, Nuvem, Dados, Portais...
	10 	3.7k 	Mar 2024
API - Compras Dados GOV - INSTABILIDADE CONSTANTE
TIC, Nuvem, Dados, Portais...

    licitação

	5 	510 	May 2024
Powered by Discourse
