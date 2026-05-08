import numpy as np
import pandas as pd
import requests

# Lista de cÃ³digos de item do catÃ¡logo a serem consultados
codigos_item_catalogo = [INSERIR CATMAT SEPARADO POR VÃRGULA]

# Crie um DataFrame vazio para armazenar os resultados
df_final = pd.DataFrame()

# Defina as colunas desejadas
colunas_desejadas = [
    'idCompra', 'numeroItemCompra', 'descricaoItem', 'codigoItemCatalogo',
    'siglaUnidadeMedida', 'nomeUnidadeFornecimento', 'siglaUnidadeFornecimento',
    'capacidadeUnidadeFornecimento', 'quantidade', 'precoUnitario',
    'niFornecedor', 'nomeFornecedor', 'marca', 'codigoUasg', 'nomeUasg',
    'estado', 'dataCompra', 'modalidade'
]  

# Itere sobre os cÃ³digos de item do catÃ¡logo
for codigo_item in codigos_item_catalogo:
    # FaÃ§a a solicitaÃ§Ã£o Ã  API
    url = f"https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial?pagina=1&tamanhoPagina=500&codigoItemCatalogo={codigo_item}"
    response = requests.get(url)
    data = response.json()

    # Converta os dados em um DataFrame do pandas
    df = pd.json_normalize(data, 'resultado')

    # Exiba o DataFrame resultante
    print(df)
    
    # Verifique se todas as colunas desejadas estÃ£o presentes
    if set(colunas_desejadas).issubset(df.columns):
        # Se sim, selecione apenas as colunas desejadas
        df = df[colunas_desejadas]

        # Concatene o DataFrame resultante ao DataFrame final
        df_final = pd.concat([df_final, df], ignore_index=True)
        
      
    # Transformando o atributo dataCompra para o formato de Data
        df_final['dataCompra'] = pd.to_datetime(df_final['dataCompra'])

        # Formatando para dia/mÃªs/ano
        df_final['data_formatada'] = df_final['dataCompra'].dt.strftime('%d/%m/%Y')
        
        # Filtrando as entradas para os meses de MÃŠS de 2023 atÃ© MÃŠS de 2024
        df_final = df_final[(df_final['dataCompra'].dt.year == 2023) & (df_final['dataCompra'].dt.month.isin([NÃšMERO INICIAL DOS MESES BUSCADOS])) | 
                            (df_final['dataCompra'].dt.year == 2024) & (df_final['dataCompra'].dt.month.isin([NÃšMERO FINAL DOS MÃŠSES BUSCADOS]))]
        
        # Adicionando zeros Ã  esquerda do nÃºmero do item de compra para ter cinco dÃ­gitos
        df_final['numeroItemCompra'] = df_final['numeroItemCompra'].astype(str).str.zfill(5)
        
        # Concatenando as colunas 'idCompra' e 'numeroItemCompra' em uma nova coluna 'iditemcompra'
        df_final['iditemcompra'] = df_final['idCompra'].astype(str) + df_final['numeroItemCompra'].astype(str)
        
        # Formatando a coluna 'ApresentaÃ§Ã£o' para ter dois algarismos apÃ³s a vÃ­rgula quando a capacidade for diferente de zero
        df_final['ApresentaÃ§Ã£o'] = df_final.apply(lambda row: f"{row['nomeUnidadeFornecimento']} {row['capacidadeUnidadeFornecimento']:.2f} {row['siglaUnidadeMedida']}".replace('.', ',') if row['capacidadeUnidadeFornecimento'] != 0 else row['nomeUnidadeFornecimento'], axis=1)
        
        # Criar um dicionÃ¡rio de mapeamento dos valores da coluna 'modalidade' para os respectivos nomes
        modalidade_mapping = {
            5: 'PregÃ£o',
            6: 'Dispensa',
            7: 'Inexigibilidade'
        }

        # Aplicar o mapeamento para criar a nova coluna 'Modalidade Compra'
        df_final['Modalidade Compra'] = df_final['modalidade'].map(modalidade_mapping)
        
    else:
        # Se nÃ£o, imprima uma mensagem informando que o cÃ³digo do item serÃ¡ excluÃ­do
        print(f"Colunas desejadas nÃ£o encontradas para o cÃ³digo de item {codigo_item}. O cÃ³digo serÃ¡ excluÃ­do.")
        
# Renomear as colunas do DataFrame final
df_final.rename(columns={
    'codigoUasg': 'UASG',
    'nomeUasg': 'Nome do Ã³rgÃ£o',
    'data_formatada': 'Data Compra',
    'Modalidade Compra': 'Modalidade Compra',
    'niFornecedor': 'CNPJ_CPF Fornecedor',
    'nomeFornecedor': 'Nome Fornecedor',
    'codigoItemCatalogo': 'CATMAT',
    'descricaoItem': 'DescriÃ§Ã£o Item',
    'ApresentaÃ§Ã£o': 'ApresentaÃ§Ã£o',
    'quantidade': 'Quantidade',
    'precoUnitario': 'PreÃ§o UnitÃ¡rio',
    'marca': 'Marca Item',
    'iditemcompra': 'IDitemCompra'
}, inplace=True)

# Reordenar as colunas do DataFrame final
df_final = df_final.reindex(columns=['UASG', 'Nome do Ã³rgÃ£o', 'Data Compra', 'Modalidade Compra', 'CNPJ_CPF Fornecedor', 'Nome Fornecedor', 'CATMAT', 'DescriÃ§Ã£o Item', 'ApresentaÃ§Ã£o', 'Quantidade', 'PreÃ§o UnitÃ¡rio', 'Marca Item', 'IDitemCompra'])

# Exibir as primeiras 30 linhas do DataFrame final
print(df_final.head(30))

# salvando os dados em uma planilha excel
df_final.to_excel('NOME DO ARQUIVO.xlsx')
