<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ServicoMapeamentoQuestionario
{
    /**
     * Mapeamento completo de todas as perguntas do questionário
     */
    public function obterMapeamentoCompleto(): array
    {
        return [
            // Dados básicos
            'nome_completo' => [
                'pergunta' => 'Qual é o seu nome completo?',
                'tipo' => 'texto',
                'regex' => '/nome[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i',
                'regex_direto' => '/^([a-záàâãéèêíìîóòôõúùûç\s]+)(?=\s+\d{1,2}\/\d{1,2}\/\d{4})/i',
                'posicao' => 1
            ],
            
            'data_nascimento' => [
                'pergunta' => 'Qual é a sua data de nascimento? (DD/MM/AAAA)',
                'tipo' => 'data',
                'regex' => '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',
                'posicao' => 2
            ],
            
            'sexo_biologico' => [
                'pergunta' => 'Qual é o seu sexo biológico? (Masculino/Feminino)',
                'tipo' => 'select',
                'opcoes' => ['Masculino', 'Feminino', 'M', 'F'],
                'regex' => '/sexo[:\s]+(masculino|feminino|m|f)/i',
                'regex_direto' => '/\b(masculino|feminino|m|f)\b/i',
                'posicao' => 3
            ],
            
            'atividade_sexual' => [
                'pergunta' => 'Já iniciou atividade sexual? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/atividade[:\s]+sexual[:\s]+(sim|não|s|n)/i',
                'regex_direto' => '/\b(masculino|feminino|m|f)\b\s+(sim|não|s|n)\b/i',
                'posicao' => 4
            ],
            
            'peso_kg' => [
                'pergunta' => 'Qual é o seu peso? (kg)',
                'tipo' => 'numero',
                'regex' => '/peso[:\s]+(\d+(?:[.,]\d+)?)\s*(?:kg)?/i',
                'posicao' => 5
            ],
            
            'altura_cm' => [
                'pergunta' => 'Qual é a sua altura? (cm)',
                'tipo' => 'numero',
                'regex' => '/altura[:\s]+(\d+(?:[.,]\d+)?)\s*(?:cm)?/i',
                'posicao' => 6
            ],
            
            'cidade' => [
                'pergunta' => 'Qual é a sua cidade?',
                'tipo' => 'texto',
                'regex' => '/cidade[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i',
                'posicao' => 7
            ],
            
            'estado' => [
                'pergunta' => 'Qual é o seu estado?',
                'tipo' => 'texto',
                'regex' => '/estado[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i',
                'posicao' => 8
            ],
            
            // Histórico pessoal
            'teve_cancer_pessoal' => [
                'pergunta' => 'Você já teve câncer? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/teve[:\s]+câncer[:\s]+(sim|não|s|n)/i',
                'posicao' => 9
            ],
            
            'parente_1grau_cancer' => [
                'pergunta' => 'Algum parente de primeiro grau já teve câncer? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/parente[:\s]+primeiro[:\s]+grau[:\s]+(sim|não|s|n)/i',
                'posicao' => 10
            ],
            
            'tipo_cancer_parente' => [
                'pergunta' => 'Qual tipo de câncer?',
                'tipo' => 'texto',
                'regex' => '/tipo[:\s]+câncer[:\s]+([a-záàâãéèêíìîóòôõúùûç\s]+)/i',
                'posicao' => 11
            ],
            
            'idade_diagnostico_parente' => [
                'pergunta' => 'Qual a idade do diagnóstico?',
                'tipo' => 'numero',
                'regex' => '/idade[:\s]+diagnóstico[:\s]+(\d+)/i',
                'posicao' => 12
            ],
            
            // Tabagismo
            'status_tabagismo' => [
                'pergunta' => 'Você fuma ou já fumou? (Nunca/Ex-fumante/Sim)',
                'tipo' => 'select',
                'opcoes' => ['Nunca', 'Ex-fumante', 'Sim'],
                'regex' => '/fuma[:\s]+ou[:\s]+já[:\s]+fumou[:\s]+(nunca|ex-fumante|sim)/i',
                'posicao' => 13
            ],
            
            'macos_dia' => [
                'pergunta' => 'Quantos maços por dia?',
                'tipo' => 'numero',
                'regex' => '/maços[:\s]+por[:\s]+dia[:\s]+(\d+(?:[.,]\d+)?)/i',
                'posicao' => 14
            ],
            
            'anos_fumando' => [
                'pergunta' => 'Há quantos anos fuma?',
                'tipo' => 'numero',
                'regex' => '/anos[:\s]+fuma[:\s]+(\d+(?:[.,]\d+)?)/i',
                'posicao' => 15
            ],
            
            // Álcool e atividade física
            'consome_alcool' => [
                'pergunta' => 'Você consome álcool? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/consome[:\s]+álcool[:\s]+(sim|não|s|n)/i',
                'posicao' => 16
            ],
            
            'pratica_atividade' => [
                'pergunta' => 'Você pratica atividade física? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/pratica[:\s]+atividade[:\s]+física[:\s]+(sim|não|s|n)/i',
                'posicao' => 17
            ],
            
            // Saúde da mulher
            'idade_primeira_menstruacao' => [
                'pergunta' => 'Qual a idade da primeira menstruação?',
                'tipo' => 'numero',
                'regex' => '/idade[:\s]+primeira[:\s]+menstruação[:\s]+(\d+)/i',
                'posicao' => 18
            ],
            
            'ja_engravidou' => [
                'pergunta' => 'Já engravidou? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/já[:\s]+engravidou[:\s]+(sim|não|s|n)/i',
                'posicao' => 19
            ],
            
            'uso_anticoncepcional' => [
                'pergunta' => 'Usa anticoncepcional? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/usa[:\s]+anticoncepcional[:\s]+(sim|não|s|n)/i',
                'posicao' => 20
            ],
            
            'fez_papanicolau' => [
                'pergunta' => 'Já fez Papanicolau? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/já[:\s]+fez[:\s]+papanicolau[:\s]+(sim|não|s|n)/i',
                'posicao' => 21
            ],
            
            'ano_ultimo_papanicolau' => [
                'pergunta' => 'Ano do último Papanicolau?',
                'tipo' => 'numero',
                'regex' => '/ano[:\s]+último[:\s]+papanicolau[:\s]+(\d{4})/i',
                'posicao' => 22
            ],
            
            'fez_mamografia' => [
                'pergunta' => 'Já fez mamografia? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/já[:\s]+fez[:\s]+mamografia[:\s]+(sim|não|s|n)/i',
                'posicao' => 23
            ],
            
            'ano_ultima_mamografia' => [
                'pergunta' => 'Ano da última mamografia?',
                'tipo' => 'numero',
                'regex' => '/ano[:\s]+última[:\s]+mamografia[:\s]+(\d{4})/i',
                'posicao' => 24
            ],
            
            'hist_fam_mama_ovario' => [
                'pergunta' => 'Histórico familiar de câncer de mama/ovário? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/histórico[:\s]+familiar[:\s]+mama[:\s]+ovário[:\s]+(sim|não|s|n)/i',
                'posicao' => 25
            ],
            
            // Saúde do homem
            'fez_rastreamento_prostata' => [
                'pergunta' => 'Já fez rastreamento de próstata? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/já[:\s]+fez[:\s]+rastreamento[:\s]+próstata[:\s]+(sim|não|s|n)/i',
                'posicao' => 26
            ],
            
            'deseja_info_prostata' => [
                'pergunta' => 'Deseja informações sobre próstata? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/deseja[:\s]+informações[:\s]+próstata[:\s]+(sim|não|s|n)/i',
                'posicao' => 27
            ],
            
            // Rastreamento colorretal
            'mais_de_45_anos' => [
                'pergunta' => 'Tem mais de 45 anos? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/mais[:\s]+de[:\s]+45[:\s]+anos[:\s]+(sim|não|s|n)/i',
                'posicao' => 28
            ],
            
            'parente_1grau_colorretal' => [
                'pergunta' => 'Parente de primeiro grau com câncer colorretal? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/parente[:\s]+primeiro[:\s]+grau[:\s]+colorretal[:\s]+(sim|não|s|n)/i',
                'posicao' => 29
            ],
            
            'fez_exame_colorretal' => [
                'pergunta' => 'Já fez exame colorretal? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/já[:\s]+fez[:\s]+exame[:\s]+colorretal[:\s]+(sim|não|s|n)/i',
                'posicao' => 30
            ],
            
            'ano_ultimo_exame_colorretal' => [
                'pergunta' => 'Ano do último exame colorretal?',
                'tipo' => 'numero',
                'regex' => '/ano[:\s]+último[:\s]+exame[:\s]+colorretal[:\s]+(\d{4})/i',
                'posicao' => 31
            ],
            
            // Sinais de alerta
            'sinais_alerta_intestino' => [
                'pergunta' => 'Sinais de alerta intestinal? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/sinais[:\s]+alerta[:\s]+intestinal[:\s]+(sim|não|s|n)/i',
                'posicao' => 32
            ],
            
            'sangramento_anormal' => [
                'pergunta' => 'Sangramento anormal? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/sangramento[:\s]+anormal[:\s]+(sim|não|s|n)/i',
                'posicao' => 33
            ],
            
            'tosse_persistente' => [
                'pergunta' => 'Tosse persistente? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/tosse[:\s]+persistente[:\s]+(sim|não|s|n)/i',
                'posicao' => 34
            ],
            
            'nodulos_palpaveis' => [
                'pergunta' => 'Nódulos palpáveis? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/nódulos[:\s]+palpáveis[:\s]+(sim|não|s|n)/i',
                'posicao' => 35
            ],
            
            'perda_peso_nao_intencional' => [
                'pergunta' => 'Perda de peso não intencional? (Sim/Não)',
                'tipo' => 'boolean',
                'opcoes' => ['Sim', 'Não', 'S', 'N'],
                'regex' => '/perda[:\s]+peso[:\s]+não[:\s]+intencional[:\s]+(sim|não|s|n)/i',
                'posicao' => 36
            ]
        ];
    }
    
    /**
     * Extrair dados do resumo completo da IA
     */
    public function extrairDadosDoResumoCompleto(string $resumoIA): array
    {
        Log::info('🔍 Extraindo dados do resumo completo da IA:', ['resumo' => $resumoIA]);
        
        $dados = [];
        $mapeamento = $this->obterMapeamentoCompleto();
        
        foreach ($mapeamento as $campo => $config) {
            $valor = $this->extrairCampoEspecifico($resumoIA, $campo, $config);
            if ($valor !== null) {
                $dados[$campo] = $valor;
                Log::info("✅ Campo extraído: {$campo}", ['valor' => $valor]);
            }
        }
        
        Log::info('📊 Dados extraídos do resumo completo:', $dados);
        
        return $dados;
    }
    
    /**
     * Extrair campo específico do resumo
     */
    private function extrairCampoEspecifico(string $resumo, string $campo, array $config): mixed
    {
        // Tentar regex principal
        if (isset($config['regex']) && preg_match($config['regex'], $resumo, $matches)) {
            return $this->processarValor($matches[1], $config['tipo']);
        }
        
        // Tentar regex direto se disponível
        if (isset($config['regex_direto']) && preg_match($config['regex_direto'], $resumo, $matches)) {
            return $this->processarValor($matches[1], $config['tipo']);
        }
        
        return null;
    }
    
    /**
     * Processar valor baseado no tipo
     */
    private function processarValor(string $valor, string $tipo): mixed
    {
        $valor = trim($valor);
        
        switch ($tipo) {
            case 'boolean':
                return in_array(strtolower($valor), ['sim', 's', 'true', '1']);
                
            case 'numero':
                return (float) str_replace(',', '.', $valor);
                
            case 'data':
                if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $valor, $matches)) {
                    $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                    $ano = $matches[3];
                    return "{$ano}-{$mes}-{$dia}";
                }
                return $valor;
                
            case 'select':
                return $valor;
                
            case 'texto':
            default:
                return $valor;
        }
    }
}
