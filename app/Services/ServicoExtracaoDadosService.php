<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ServicoExtracaoDadosService
{
    /**
     * Extrai dados do resumo da OpenAI
     */
    public function extrairDadosDoResumo(string $resumoOpenAI): array
    {
        Log::info('🔍 Extraindo dados do resumo da OpenAI:', ['resumo' => $resumoOpenAI]);
        
        $dados = [];
        
        // Detectar nome no resumo
        if (preg_match('/nome[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $resumoOpenAI, $matches)) {
            $nome = trim($matches[1]);
            if (!str_contains(strtolower($nome), 'encerrar') && strlen($nome) > 2) {
                $dados['nome_completo'] = $nome;
                Log::info('✅ Nome extraído do resumo:', ['nome' => $nome]);
            }
        }
        
        // Detectar data de nascimento
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $resumoOpenAI, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $ano = $matches[3];
            $dados['data_nascimento'] = "{$ano}-{$mes}-{$dia}";
            Log::info('✅ Data extraída do resumo:', ['data' => $dados['data_nascimento']]);
        }
        
        // Detectar sexo biológico
        if (preg_match('/sexo[:\s]+(masculino|feminino|m|f)/i', $resumoOpenAI, $matches)) {
            $sexo = strtolower($matches[1]);
            $dados['sexo_biologico'] = ($sexo === 'm' || $sexo === 'masculino') ? 'M' : 'F';
            Log::info('✅ Sexo extraído do resumo:', ['sexo' => $dados['sexo_biologico']]);
        }
        
        // Detectar atividade sexual
        if (preg_match('/atividade[:\s]+sexual[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['atividade_sexual'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Atividade sexual extraída do resumo:', ['valor' => $dados['atividade_sexual']]);
        }
        
        // Detectar peso
        if (preg_match('/peso[:\s]+(\d+(?:[.,]\d+)?)\s*(?:kg)?/i', $resumoOpenAI, $matches)) {
            $dados['peso_kg'] = (float) str_replace(',', '.', $matches[1]);
            Log::info('✅ Peso extraído do resumo:', ['peso' => $dados['peso_kg']]);
        }
        
        // Detectar altura
        if (preg_match('/altura[:\s]+(\d+(?:[.,]\d+)?)\s*(?:cm|m)?/i', $resumoOpenAI, $matches)) {
            $altura = (float) str_replace(',', '.', $matches[1]);
            if ($altura < 3) {
                $altura = $altura * 100;
            }
            $dados['altura_cm'] = $altura;
            Log::info('✅ Altura extraída do resumo:', ['altura' => $dados['altura_cm']]);
        }
        
        // Detectar cidade/estado
        if (preg_match('/cidade[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)\s*\/\s*([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $resumoOpenAI, $matches)) {
            $dados['cidade'] = trim($matches[1]);
            $dados['estado'] = trim($matches[2]);
            Log::info('✅ Cidade/Estado extraídos do resumo:', ['cidade' => $dados['cidade'], 'estado' => $dados['estado']]);
        }
        
        // Detectar tabagismo
        if (preg_match('/fuma[:\s]+(nunca|ex-fumante|sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $status = strtolower($matches[1]);
            if ($status === 'nunca') {
                $dados['status_tabagismo'] = 'Nunca';
            } elseif ($status === 'ex-fumante') {
                $dados['status_tabagismo'] = 'Ex-fumante';
            } else {
                $dados['status_tabagismo'] = 'Sim';
            }
            Log::info('✅ Tabagismo extraído do resumo:', ['status' => $dados['status_tabagismo']]);
        }
        
        // Detectar consumo de álcool
        if (preg_match('/álcool[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['consome_alcool'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Consumo de álcool extraído do resumo:', ['valor' => $dados['consome_alcool']]);
        }
        
        // Detectar atividade física
        if (preg_match('/atividade[:\s]+física[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['pratica_atividade'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Atividade física extraída do resumo:', ['valor' => $dados['pratica_atividade']]);
        }
        
        // Detectar câncer pessoal
        if (preg_match('/câncer[:\s]+pessoal[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['teve_cancer_pessoal'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Câncer pessoal extraído do resumo:', ['valor' => $dados['teve_cancer_pessoal']]);
        }
        
        // Detectar câncer familiar
        if (preg_match('/parente[:\s]+câncer[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['parente_1grau_cancer'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Câncer familiar extraído do resumo:', ['valor' => $dados['parente_1grau_cancer']]);
        }
        
        // Detectar sinais de alerta
        if (preg_match('/sangramento[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['sangramento_anormal'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Sangramento extraído do resumo:', ['valor' => $dados['sangramento_anormal']]);
        }
        
        if (preg_match('/tosse[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['tosse_persistente'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Tosse extraída do resumo:', ['valor' => $dados['tosse_persistente']]);
        }
        
        if (preg_match('/nódulo[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['nodulos_palpaveis'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Nódulos extraídos do resumo:', ['valor' => $dados['nodulos_palpaveis']]);
        }
        
        if (preg_match('/perda[:\s]+peso[:\s]+(sim|não|s|n)/i', $resumoOpenAI, $matches)) {
            $dados['perda_peso_nao_intencional'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Perda de peso extraída do resumo:', ['valor' => $dados['perda_peso_nao_intencional']]);
        }
        
        // Calcular prioridade baseada nos sinais de alerta
        $sinaisAlerta = [
            $dados['sangramento_anormal'] ?? false,
            $dados['tosse_persistente'] ?? false,
            $dados['nodulos_palpaveis'] ?? false,
            $dados['perda_peso_nao_intencional'] ?? false
        ];
        
        $dados['precisa_atendimento_prioritario'] = in_array(true, $sinaisAlerta);
        
        Log::info('📊 Dados extraídos do resumo da OpenAI:', $dados);
        
        return $dados;
    }

    /**
     * Extrai dados estruturados das mensagens do chat
     */
    public function extrairDadosDasMensagens(array $mensagens): array
    {
        $dados = [];
        $textoCompleto = '';
        
        // Concatenar todas as mensagens
        foreach ($mensagens as $mensagem) {
            $textoCompleto .= ' ' . $mensagem['text'];
        }
        
        Log::info('🔍 Extraindo dados do texto completo:', ['texto' => $textoCompleto]);
        
        // Detectar nome (não capturar "Encerrar")
        if (preg_match('/nome[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $textoCompleto, $matches)) {
            $nome = trim($matches[1]);
            if (!str_contains(strtolower($nome), 'encerrar')) {
                $dados['nome_completo'] = $nome;
                Log::info('✅ Nome extraído:', ['nome' => $nome]);
            }
        }
        
        // Detectar data de nascimento
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $textoCompleto, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $ano = $matches[3];
            $dados['data_nascimento'] = "{$ano}-{$mes}-{$dia}";
            Log::info('✅ Data extraída:', ['data' => $dados['data_nascimento']]);
        }
        
        // Detectar sexo biológico
        if (preg_match('/sexo[:\s]+(masculino|feminino|m|f)/i', $textoCompleto, $matches)) {
            $sexo = strtolower($matches[1]);
            $dados['sexo_biologico'] = ($sexo === 'm' || $sexo === 'masculino') ? 'M' : 'F';
            Log::info('✅ Sexo extraído:', ['sexo' => $dados['sexo_biologico']]);
        }
        
        // Detectar atividade sexual
        if (preg_match('/atividade[:\s]+sexual[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['atividade_sexual'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Atividade sexual extraída:', ['valor' => $dados['atividade_sexual']]);
        }
        
        // Detectar peso
        if (preg_match('/peso[:\s]+(\d+(?:[.,]\d+)?)\s*(?:kg)?/i', $textoCompleto, $matches)) {
            $dados['peso_kg'] = (float) str_replace(',', '.', $matches[1]);
            Log::info('✅ Peso extraído:', ['peso' => $dados['peso_kg']]);
        }
        
        // Detectar altura
        if (preg_match('/altura[:\s]+(\d+(?:[.,]\d+)?)\s*(?:cm|m)?/i', $textoCompleto, $matches)) {
            $altura = (float) str_replace(',', '.', $matches[1]);
            // Se altura < 3, provavelmente está em metros, converter para cm
            if ($altura < 3) {
                $altura = $altura * 100;
            }
            $dados['altura_cm'] = $altura;
            Log::info('✅ Altura extraída:', ['altura' => $dados['altura_cm']]);
        }
        
        // Detectar cidade/estado
        if (preg_match('/cidade[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)\s*\/\s*([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $textoCompleto, $matches)) {
            $dados['cidade'] = trim($matches[1]);
            $dados['estado'] = trim($matches[2]);
            Log::info('✅ Cidade/Estado extraídos:', ['cidade' => $dados['cidade'], 'estado' => $dados['estado']]);
        }
        
        // Detectar tabagismo
        if (preg_match('/fuma[:\s]+(nunca|ex-fumante|sim|não|s|n)/i', $textoCompleto, $matches)) {
            $status = strtolower($matches[1]);
            if ($status === 'nunca') {
                $dados['status_tabagismo'] = 'Nunca';
            } elseif ($status === 'ex-fumante') {
                $dados['status_tabagismo'] = 'Ex-fumante';
            } else {
                $dados['status_tabagismo'] = 'Sim';
            }
            Log::info('✅ Tabagismo extraído:', ['status' => $dados['status_tabagismo']]);
        }
        
        // Detectar consumo de álcool
        if (preg_match('/álcool[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['consome_alcool'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Consumo de álcool extraído:', ['valor' => $dados['consome_alcool']]);
        }
        
        // Detectar atividade física
        if (preg_match('/atividade[:\s]+física[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['pratica_atividade'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Atividade física extraída:', ['valor' => $dados['pratica_atividade']]);
        }
        
        // Detectar câncer pessoal
        if (preg_match('/câncer[:\s]+pessoal[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['teve_cancer_pessoal'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Câncer pessoal extraído:', ['valor' => $dados['teve_cancer_pessoal']]);
        }
        
        // Detectar câncer familiar
        if (preg_match('/parente[:\s]+câncer[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['parente_1grau_cancer'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Câncer familiar extraído:', ['valor' => $dados['parente_1grau_cancer']]);
        }
        
        // Detectar sinais de alerta
        if (preg_match('/sangramento[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['sangramento_anormal'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Sangramento extraído:', ['valor' => $dados['sangramento_anormal']]);
        }
        
        if (preg_match('/tosse[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['tosse_persistente'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Tosse extraída:', ['valor' => $dados['tosse_persistente']]);
        }
        
        if (preg_match('/nódulo[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['nodulos_palpaveis'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Nódulos extraídos:', ['valor' => $dados['nodulos_palpaveis']]);
        }
        
        if (preg_match('/perda[:\s]+peso[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['perda_peso_nao_intencional'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Perda de peso extraída:', ['valor' => $dados['perda_peso_nao_intencional']]);
        }
        
        // Calcular prioridade baseada nos sinais de alerta
        $sinaisAlerta = [
            $dados['sangramento_anormal'] ?? false,
            $dados['tosse_persistente'] ?? false,
            $dados['nodulos_palpaveis'] ?? false,
            $dados['perda_peso_nao_intencional'] ?? false
        ];
        
        $dados['precisa_atendimento_prioritario'] = in_array(true, $sinaisAlerta);
        
        Log::info('📊 Dados extraídos pelo backend:', $dados);
        
        return $dados;
    }
}
