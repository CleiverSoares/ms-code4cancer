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
        
        // Detectar nome (primeira palavra/frase que não seja data, sexo ou "Encerrar")
        if (preg_match('/nome[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $textoCompleto, $matches)) {
            $nome = trim($matches[1]);
            if (!str_contains(strtolower($nome), 'encerrar')) {
                $dados['nome_completo'] = $nome;
                Log::info('✅ Nome extraído:', ['nome' => $nome]);
            }
        } else {
            // Tentar extrair nome diretamente do texto (primeira palavra/frase)
            $palavras = explode(' ', trim($textoCompleto));
            $nome = '';
            
            foreach ($palavras as $palavra) {
                $palavra = trim($palavra);
                
                // Parar se encontrar data, sexo ou "Encerrar"
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $palavra) || 
                    in_array(strtolower($palavra), ['masculino', 'feminino', 'm', 'f', 'encerrar'])) {
                    break;
                }
                
                // Adicionar palavra ao nome se não for vazia
                if (!empty($palavra)) {
                    $nome .= ($nome ? ' ' : '') . $palavra;
                }
            }
            
            if (!empty($nome) && strlen($nome) > 2) {
                $dados['nome_completo'] = $nome;
                Log::info('✅ Nome extraído diretamente:', ['nome' => $nome]);
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
        
        // Detectar sexo biológico (primeiro método: com "sexo:")
        if (preg_match('/sexo[:\s]+(masculino|feminino|m|f)/i', $textoCompleto, $matches)) {
            $sexo = strtolower($matches[1]);
            $dados['sexo_biologico'] = ($sexo === 'm' || $sexo === 'masculino') ? 'M' : 'F';
            Log::info('✅ Sexo extraído:', ['sexo' => $dados['sexo_biologico']]);
        } else {
            // Segundo método: procurar diretamente no texto
            if (preg_match('/\b(masculino|feminino|m|f)\b/i', $textoCompleto, $matches)) {
                $sexo = strtolower($matches[1]);
                $dados['sexo_biologico'] = ($sexo === 'm' || $sexo === 'masculino') ? 'M' : 'F';
                Log::info('✅ Sexo extraído diretamente:', ['sexo' => $dados['sexo_biologico']]);
            }
        }
        
        // Detectar atividade sexual (primeiro método: com "atividade sexual:")
        if (preg_match('/atividade[:\s]+sexual[:\s]+(sim|não|s|n)/i', $textoCompleto, $matches)) {
            $dados['atividade_sexual'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Atividade sexual extraída:', ['valor' => $dados['atividade_sexual']]);
        } else {
            // Segundo método: procurar "Sim" ou "Não" após sexo
            if (preg_match('/\b(masculino|feminino|m|f)\b\s+(sim|não|s|n)\b/i', $textoCompleto, $matches)) {
                $dados['atividade_sexual'] = in_array(strtolower($matches[2]), ['sim', 's']);
                Log::info('✅ Atividade sexual extraída diretamente:', ['valor' => $dados['atividade_sexual']]);
            }
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
        
        // Detectar cidade/estado (múltiplos formatos)
        if (preg_match('/cidade[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)\s*\/\s*([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $textoCompleto, $matches)) {
            $dados['cidade'] = trim($matches[1]);
            $dados['estado'] = trim($matches[2]);
            Log::info('✅ Cidade/Estado extraídos (formato cidade/estado):', ['cidade' => $dados['cidade'], 'estado' => $dados['estado']]);
        } elseif (preg_match('/([a-záàâãéèêíìîóòôõúùûç\s]+)\s*,\s*([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $textoCompleto, $matches)) {
            // Formato: "Cidade, Estado" - procurar por padrão cidade, estado
            $cidade = trim($matches[1]);
            $estado = trim($matches[2]);
            
            // Validar se não são números ou palavras muito curtas
            if (strlen($cidade) > 2 && strlen($estado) <= 3 && !is_numeric($cidade) && !is_numeric($estado)) {
                $dados['cidade'] = $cidade;
                $dados['estado'] = strtoupper($estado);
                Log::info('✅ Cidade/Estado extraídos (formato cidade, estado):', ['cidade' => $dados['cidade'], 'estado' => $dados['estado']]);
            }
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
    
    /**
     * Extrai dados do resumo completo da IA e salva o resumo
     */
    public function extrairDadosDoResumoCompleto(string $resumoIA): array
    {
        Log::info('🔍 Extraindo dados do resumo completo da IA:', ['resumo' => $resumoIA]);
        
        $dados = [];
        
        // Detectar nome no resumo
        if (preg_match('/nome[:\s]+completo[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $resumoIA, $matches)) {
            $nome = trim($matches[1]);
            if (!str_contains(strtolower($nome), 'encerrar') && strlen($nome) > 2) {
                $dados['nome_completo'] = $nome;
                Log::info('✅ Nome extraído do resumo:', ['nome' => $nome]);
            }
        }
        
        // Detectar data de nascimento
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $resumoIA, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $ano = $matches[3];
            $dados['data_nascimento'] = "{$ano}-{$mes}-{$dia}";
            Log::info('✅ Data extraída do resumo:', ['data' => $dados['data_nascimento']]);
        }
        
        // Detectar sexo biológico
        if (preg_match('/sexo[:\s]+biológico[:\s]+(masculino|feminino|m|f)/i', $resumoIA, $matches)) {
            $sexo = strtolower($matches[1]);
            $dados['sexo_biologico'] = ($sexo === 'm' || $sexo === 'masculino') ? 'M' : 'F';
            Log::info('✅ Sexo extraído do resumo:', ['sexo' => $dados['sexo_biologico']]);
        }
        
        // Detectar atividade sexual
        if (preg_match('/atividade[:\s]+sexual[:\s]+(sim|não|s|n)/i', $resumoIA, $matches)) {
            $dados['atividade_sexual'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Atividade sexual extraída do resumo:', ['valor' => $dados['atividade_sexual']]);
        }
        
        // Detectar peso
        if (preg_match('/peso[:\s]+(\d+(?:[.,]\d+)?)\s*(?:kg)?/i', $resumoIA, $matches)) {
            $dados['peso_kg'] = (float) str_replace(',', '.', $matches[1]);
            Log::info('✅ Peso extraído do resumo:', ['peso' => $dados['peso_kg']]);
        }
        
        // Detectar altura
        if (preg_match('/altura[:\s]+(\d+(?:[.,]\d+)?)\s*(?:cm)?/i', $resumoIA, $matches)) {
            $dados['altura_cm'] = (float) str_replace(',', '.', $matches[1]);
            Log::info('✅ Altura extraída do resumo:', ['altura' => $dados['altura_cm']]);
        }
        
        // Detectar cidade (formato "Cidade / Estado: São Paulo, SP")
        if (preg_match('/cidade[:\s]+\/?\s*estado[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+),\s*([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $resumoIA, $matches)) {
            $cidade = trim($matches[1]);
            $estado = trim($matches[2]);
            if ($cidade !== 'Não informado' && $cidade !== 'Não aplicável' && strlen($cidade) <= 100) {
                $dados['cidade'] = $cidade;
                $dados['estado'] = strtoupper($estado);
                Log::info('✅ Cidade/Estado extraídos do resumo:', ['cidade' => $dados['cidade'], 'estado' => $dados['estado']]);
            }
        }
        
        // Detectar estado
        if (preg_match('/estado[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i', $resumoIA, $matches)) {
            $estado = trim($matches[1]);
            // Só aceitar estados válidos de 2 caracteres
            if ($estado !== 'Não informado' && $estado !== 'Não aplicável' && strlen($estado) === 2) {
                $dados['estado'] = strtoupper($estado);
                Log::info('✅ Estado extraído do resumo:', ['estado' => $dados['estado']]);
            }
        }
        
        // Detectar histórico pessoal de câncer (formato "Já teve algum câncer diagnosticado: Não")
        if (preg_match('/já[:\s]+teve[:\s]+algum[:\s]+câncer[:\s]+diagnosticado[:\s]+(sim|não|s|n)/i', $resumoIA, $matches)) {
            $dados['teve_cancer_pessoal'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Histórico pessoal extraído do resumo:', ['valor' => $dados['teve_cancer_pessoal']]);
        }
        
        // Detectar histórico familiar
        if (preg_match('/parente[:\s]+primeiro[:\s]+grau[:\s]+(sim|não|s|n)/i', $resumoIA, $matches)) {
            $dados['parente_1grau_cancer'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Histórico familiar extraído do resumo:', ['valor' => $dados['parente_1grau_cancer']]);
        }
        
        // Detectar tabagismo
        if (preg_match('/fuma[:\s]+ou[:\s]+já[:\s]+fumou[:\s]+(nunca|ex-fumante|sim)/i', $resumoIA, $matches)) {
            $dados['status_tabagismo'] = ucfirst($matches[1]);
            Log::info('✅ Tabagismo extraído do resumo:', ['status' => $dados['status_tabagismo']]);
        }
        
        // Detectar consumo de álcool
        if (preg_match('/consome[:\s]+álcool[:\s]+(sim|não|s|n)/i', $resumoIA, $matches)) {
            $dados['consome_alcool'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Consumo de álcool extraído do resumo:', ['valor' => $dados['consome_alcool']]);
        }
        
        // Detectar atividade física
        if (preg_match('/pratica[:\s]+atividade[:\s]+física[:\s]+(sim|não|s|n)/i', $resumoIA, $matches)) {
            $dados['pratica_atividade'] = in_array(strtolower($matches[1]), ['sim', 's']);
            Log::info('✅ Atividade física extraída do resumo:', ['valor' => $dados['pratica_atividade']]);
        }
        
        // Adicionar o resumo completo aos dados
        $dados['resumo_ia'] = $resumoIA;
        Log::info('✅ Resumo da IA salvo:', ['tamanho' => strlen($resumoIA)]);
        
        Log::info('📊 Dados extraídos do resumo completo:', $dados);
        
        return $dados;
    }
}
