Esta documentação foi elaborada para ser entregue à equipe de desenvolvimento responsável pelo sistema do **Diário Oficial do Município de Assaí**. Ela detalha os requisitos técnicos, protocolos de comunicação e esquemas de dados necessários para a integração com o barramento de assinaturas **Assina Assaí (LibreSign/Nextcloud)**.

---

# Especificação Técnica de Integração: Sistema de Diário Oficial <-> Assina Assaí

## 1. Protocolo de Comunicação e Autenticação

A integração é baseada em uma arquitetura **REST API** utilizando o protocolo **OCS (Open Collaboration Services)** do Nextcloud.

* **Endpoint Base:** `https://assinador.assai.pr.gov.br/ocs/v2.php/apps/libresign/api/v1`
* **Headers Obrigatórios:**
* `OCS-APIRequest: true` (Essencial para bypass de CSRF)
* `Accept: application/json`
* `Content-Type: application/json`


* **Autenticação:** Basic Auth utilizando **App Password**.
* *Nota:* O sistema de origem (Diário) deve utilizar uma senha de aplicativo gerada no perfil administrativo para ignorar desafios de MFA (Multi-Factor Authentication).



---

## 2. Requisitos do Arquivo de Entrada (Input)

Para garantir a validade jurídica e a conformidade com a **Lei 14.063/2020 (Assinatura Avançada)**:

* **Formato de Arquivo:** Exclusivamente **PDF**. Recomenda-se o padrão **PDF/A-1b** ou **PDF/A-2b** para preservação a longo prazo.
* **Codificação de Envio:** O arquivo deve ser enviado em **Base64** dentro do payload JSON ou via **URL de acesso direto** (acessível pelo servidor de assinatura).
* **Tamanho Máximo:** Limitado a **100MB** (configuração atual do proxy reverso Nginx).

---

## 3. Fluxo de Assinatura via API (Step-by-Step)

### Passo 1: Solicitação de Assinatura (POST `/request-signature`)

O sistema do Diário Oficial deve enviar o documento e definir os metadados do assinante.

**Schema do Payload:**

```json
{
  "name": "DIARIO_OFICIAL_EDICAO_XXXX_2026.pdf",
  "file": {
    "url": "URL_DO_ARQUIVO_NO_SISTEMA_ORIGEM" 
  },
  "signers": [
    {
      "identifyMethods": [
        {
          "method": "account",
          "value": "usuario_assinante_nextcloud",
          "mandatory": 1
        }
      ]
    }
  ],
  "visual_signature": {
    "position": {
      "page": 1,
      "x": 50,
      "y": 50
    },
    "width": 350,
    "height": 150
  }
}

```

### Passo 2: Extração de Metadados e IDs

O retorno desta chamada entregará o `uuid` (File UUID). O sistema de origem deve armazenar este UUID para consultas de status e download.

### Passo 3: Monitoramento de Status (GET `/file/list`)

O sistema deve realizar *polling* ou aguardar *webhook* para detectar o status `3` (**Signed**).

---

## 4. Estrutura de Metadados da Assinatura (Output)

O Assinador Assaí injeta automaticamente os metadados no documento. A equipe de dev do Diário Oficial deve mapear os seguintes campos que serão renderizados no **Selo de Autenticidade**:

### A. Metadados do Certificado Digital (PAdES)

* **Emissão:** `IssuerCommonName`
* **Validade:** `CertificateExpirationDate`
* **Versão:** `CertificateVersion` (Padrão X.509 v3)
* **Número Serial:** `CertificateSerialNumber`
* **Hash do Certificado:** `CertificateHash` (SHA-256)

### B. Metadados do Carimbo de Tempo (Timestamp - RFC 3161)

O servidor utiliza o motor **JSignPdf** para comunicação com a Autoridade de Carimbo de Tempo (TSA).

* **Data e Hora:** `ServerSignatureDate` (Proveniente de fonte de tempo fidedigna, não do relógio local).
* **Servidor:** `TSAServerName`
* **Política:** `TSAPolicyID`

### C. Informações do Assinante

* **Nome do Responsável:** `SignerCommonName`
* **CPF:** Extraído via atributo `TaxID` do diretório LDAP/Nextcloud.
* **E-mail:** `SignerEmail`
* **ID do Assinante:** `SignRequestUuid` (Identificador único da sessão de assinatura).

---

## 5. Formatos de Saída e Validação

Ao final do fluxo, o sistema do Diário Oficial deve recuperar o arquivo final.

* **Método de Recuperação:** `GET /file/list?details=1&uuid={uuid}`
* **Campo de Download:** `ocs.data.data.file.url`
* **Validação Pública:** O sistema do Diário deve exibir o link de validação pública:
`https://assinador.assai.pr.gov.br/apps/libresign/validation/{file_uuid}`

---

## 6. Considerações de Segurança para os Devs

1. **Tratamento de Erros:** Implementar tratativas para HTTP 412 (falta de Header OCS) e HTTP 401 (App Password inválida).
2. **Idempotência:** O File UUID deve ser a chave primária de controle no Banco de Dados do Diário Oficial para evitar duplicidade de assinaturas sobre o mesmo documento.
3. **Logs de Auditoria:** É obrigatório persistir o `SignRequestUuid` em log de banco de dados para rastreabilidade jurídica conforme exigido pela fiscalização municipal.

---

**Aprovação Técnica:**
*Kawan Harshe Kakubo*
*Divisão de Ciência, Tecnologia e Inovação - Assaí/PR*