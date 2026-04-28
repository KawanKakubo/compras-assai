<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11pt; color: #333; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header .gov-name { font-weight: bold; font-size: 14pt; }
        .header .secretaria-name { font-size: 12pt; }
        h1 { text-align: center; font-size: 16pt; margin-top: 0; }
        h2 { font-size: 12pt; border-left: 4px solid #333; padding-left: 10px; margin-top: 20px; background: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 9pt; }
        th { background: #eee; font-weight: bold; }
        .num { text-align: right; }
        .total-row { font-weight: bold; background: #f0f0f0; }
        .signatures { margin-top: 50px; }
        .signature-block { width: 45%; display: inline-block; vertical-align: top; text-align: center; margin-bottom: 30px; }
        .signature-line { border-top: 1px solid #000; margin-top: 40px; width: 80%; margin-left: auto; margin-right: auto; }
        .name { font-weight: bold; font-size: 10pt; }
        .role { font-size: 9pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="gov-name">Município de Assaí - Estado do Paraná</div>
        <div class="secretaria-name">{{ $secretarias[$procurementRequest->secretaria] ?? $procurementRequest->requisition_unit }}</div>
    </div>

    <h1>DOCUMENTO DE FORMALIZAÇÃO DE DEMANDA - DFD / SD</h1>
    
    <p><strong>Número da Solicitação:</strong> {{ $procurementRequest->reference_code }}</p>
    <p><strong>Data:</strong> {{ $procurementRequest->created_at->format('d/m/Y') }}</p>

    <h2>1. JUSTIFICATIVA DA NECESSIDADE DA CONTRATAÇÃO</h2>
    <p>{{ $procurementRequest->need_justification }}</p>

    <h2>2. DESCRIÇÃO SUCINTA DO OBJETO</h2>
    <p>{{ $procurementRequest->title }}. {{ $procurementRequest->object_summary }}</p>

    <h2>3. ESTIMATIVA DE QUANTIDADES E PREÇOS</h2>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>CATMAT/SER</th>
                <th>Descrição</th>
                <th>Und</th>
                <th>Qtd</th>
                <th>V. Unit (R$)</th>
                <th>V. Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $item->catalog_code }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->unit }}</td>
                    <td class="num">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($item->unit_value, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($item->total_value, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" style="text-align: right;">VALOR TOTAL ESTIMADO</td>
                <td class="num">{{ number_format($totalEstimated, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>4. PRIORIDADE</h2>
    <p>O grau de prioridade desta contratação é: {{ $prioridades[$procurementRequest->priority_level] ?? 'Média' }}.</p>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="name">{{ $procurementRequest->requester_name }}</div>
            <div class="role">{{ $procurementRequest->requester_role }}</div>
            <div class="role">CPF: {{ $procurementRequest->requester_cpf }}</div>
        </div>
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="name">{{ $procurementRequest->responsible_name }}</div>
            <div class="role">{{ $procurementRequest->responsible_role }}</div>
            <div class="role">CPF: {{ $procurementRequest->responsible_cpf }}</div>
        </div>
    </div>
</body>
</html>
