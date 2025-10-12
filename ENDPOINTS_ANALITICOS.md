# 📊 **ENDPOINTS ANALÍTICOS - QUESTIONÁRIOS DE RASTREAMENTO**

## 🎯 **VISÃO GERAL**

Sistema completo de endpoints analíticos para dashboard de rastreamento de câncer, com filtros avançados e dados estatísticos detalhados.

---

## 📡 **ENDPOINTS DISPONÍVEIS**

### **1. 📈 DASHBOARD GERAL DE RASTREAMENTO**
```
GET /api/questionarios/dashboard
```

**Filtros disponíveis:**
- `sexo` - F, M, O
- `faixa_etaria` - 18-29, 30-39, 40-49, 50-59, 60+
- `estado` - Sigla do estado (SP, RJ, MG, etc.)
- `cidade` - Nome da cidade
- `data_inicio` - Data início (YYYY-MM-DD)
- `data_fim` - Data fim (YYYY-MM-DD)
- `status_tabagismo` - Nunca, Ex-fumante, Sim
- `tem_sinais_alerta` - true/false
- `elegivel_rastreamento` - cervical, mamografia, prostata, colorretal

**Exemplo:**
```
GET /api/questionarios/dashboard?sexo=F&faixa_etaria=40-49&estado=SP
```

**Resposta:**
```json
{
  "sucesso": true,
  "dashboard": {
    "total_questionarios": 150,
    "distribuicao_sexo": {"F": 80, "M": 70},
    "distribuicao_idade": {"40-49": 45, "50-59": 35},
    "distribuicao_estado": {"SP": 60, "RJ": 30},
    "fatores_risco": {
      "tabagismo_ativo": 25,
      "ex_fumante": 40,
      "consome_alcool": 60,
      "sedentario": 35,
      "historico_familiar": 20
    },
    "elegibilidades": {
      "cervical": {"elegivel": 45, "nao_elegivel": 35},
      "mamografia": {"elegivel": 80, "nao_elegivel": 0},
      "prostata": {"elegivel": 0, "nao_elegivel": 80},
      "colorretal": {"elegivel": 80, "nao_elegivel": 0}
    },
    "sinais_alerta": {
      "total_com_sinais": 15,
      "sinais_especificos": {
        "sangramento_anormal": 5,
        "tosse_persistente": 8,
        "nodulos_palpaveis": 3,
        "perda_peso": 4,
        "sinais_intestino": 2
      }
    },
    "progresso_medio": 67.5
  }
}
```

---

### **2. 🔍 ANÁLISE DE FATORES DE RISCO**
```
GET /api/questionarios/analise-fatores-risco
```

**Filtros disponíveis:**
- `sexo` - F, M, O
- `faixa_etaria` - 18-29, 30-39, 40-49, 50-59, 60+
- `estado` - Sigla do estado
- `periodo` - Últimos 30 dias, 3 meses, 6 meses, 1 ano

**Exemplo:**
```
GET /api/questionarios/analise-fatores-risco?sexo=M&faixa_etaria=50-59
```

**Resposta:**
```json
{
  "sucesso": true,
  "analise": {
    "tabagismo": {
      "nunca_fumou": 30,
      "ex_fumante": 25,
      "fumante_ativo": 15,
      "anos_medio_fumando": 12.5,
      "macos_medio_dia": 1.8
    },
    "alcool": {
      "consome": 45,
      "nao_consome": 25,
      "percentual_consome": 64.3
    },
    "atividade_fisica": {
      "pratica": 35,
      "nao_pratica": 35,
      "percentual_pratica": 50.0
    },
    "historico_familiar": {
      "tem_historico": 20,
      "nao_tem_historico": 50,
      "tipos_cancer_familia": {
        "Próstata": 8,
        "Pulmão": 5,
        "Colorretal": 4,
        "Mama": 3
      }
    },
    "imc": {
      "abaixo_peso": 5,
      "peso_normal": 25,
      "sobrepeso": 30,
      "obesidade": 10,
      "imc_medio": 26.8
    }
  }
}
```

---

### **3. 🎯 ESTATÍSTICAS DE ELEGIBILIDADE**
```
GET /api/questionarios/estatisticas-elegibilidade
```

**Filtros disponíveis:**
- `sexo` - F, M, O
- `faixa_etaria` - 18-29, 30-39, 40-49, 50-59, 60+
- `tipo_rastreamento` - cervical, mamografia, prostata, colorretal

**Exemplo:**
```
GET /api/questionarios/estatisticas-elegibilidade?sexo=F&tipo_rastreamento=mamografia
```

**Resposta:**
```json
{
  "sucesso": true,
  "estatisticas": {
    "cervical": {
      "elegivel": 45,
      "nao_elegivel": 15,
      "sem_dados": 20
    },
    "mamografia": {
      "elegivel": 60,
      "nao_elegivel": 0,
      "sem_dados": 20
    },
    "prostata": {
      "elegivel": 0,
      "nao_elegivel": 80,
      "sem_dados": 0
    },
    "colorretal": {
      "elegivel": 80,
      "nao_elegivel": 0,
      "sem_dados": 0
    }
  }
}
```

---

### **4. 📊 RELATÓRIO DE PROGRESSO**
```
GET /api/questionarios/relatorio-progresso
```

**Filtros disponíveis:**
- `periodo` - Últimos 30 dias, 3 meses, 6 meses, 1 ano
- `status_progresso` - inicial, basico, intermediario, avancado, completo
- `sexo` - F, M, O

**Exemplo:**
```
GET /api/questionarios/relatorio-progresso?status_progresso=completo&sexo=F
```

**Resposta:**
```json
{
  "sucesso": true,
  "relatorio": {
    "distribuicao_progresso": {
      "inicial": 20,
      "basico": 30,
      "intermediario": 25,
      "avancado": 15,
      "completo": 10
    },
    "progresso_detalhado": [
      {
        "usuario_id": 1,
        "nome": "Maria Silva",
        "percentual": 100.0,
        "status": "completo",
        "campos_preenchidos": 33,
        "campos_totais": 33,
        "data_preenchimento": "2024-01-15T10:30:00Z"
      }
    ],
    "progresso_medio": 67.5
  }
}
```

---

### **5. 🗺️ ANÁLISE GEOGRÁFICA**
```
GET /api/questionarios/analise-geografica
```

**Filtros disponíveis:**
- `estado` - Sigla do estado
- `regiao` - Norte, Nordeste, Centro-Oeste, Sudeste, Sul
- `periodo` - Últimos 30 dias, 3 meses, 6 meses, 1 ano

**Exemplo:**
```
GET /api/questionarios/analise-geografica?regiao=Sudeste&periodo=3_meses
```

**Resposta:**
```json
{
  "sucesso": true,
  "analise": {
    "por_estado": {
      "SP": {
        "total": 60,
        "percentual": 40.0,
        "fatores_risco": {
          "tabagismo": 15,
          "alcool": 25,
          "sedentarismo": 20
        }
      },
      "RJ": {
        "total": 30,
        "percentual": 20.0,
        "fatores_risco": {
          "tabagismo": 8,
          "alcool": 12,
          "sedentarismo": 10
        }
      }
    },
    "por_cidade": {
      "São Paulo": {"total": 45, "estado": "SP"},
      "Rio de Janeiro": {"total": 25, "estado": "RJ"},
      "Belo Horizonte": {"total": 15, "estado": "MG"}
    },
    "regioes": {
      "Sudeste": 120,
      "Sul": 20,
      "Nordeste": 15,
      "Centro-Oeste": 10,
      "Norte": 5
    }
  }
}
```

---

### **6. 📈 TENDÊNCIAS TEMPORAIS**
```
GET /api/questionarios/tendencias-temporais
```

**Filtros disponíveis:**
- `periodo` - Últimos 30 dias, 3 meses, 6 meses, 1 ano
- `agrupamento` - dia, semana, mes
- `sexo` - F, M, O
- `estado` - Sigla do estado

**Exemplo:**
```
GET /api/questionarios/tendencias-temporais?agrupamento=mes&periodo=6_meses
```

**Resposta:**
```json
{
  "sucesso": true,
  "tendencias": {
    "por_periodo": {
      "2024-07": 25,
      "2024-08": 30,
      "2024-09": 35,
      "2024-10": 40,
      "2024-11": 45,
      "2024-12": 50
    },
    "crescimento": 100.0,
    "periodo_maior_crescimento": "2024-12",
    "periodo_menor_crescimento": "2024-07"
  }
}
```

---

### **7. 📋 LISTA DE QUESTIONÁRIOS**
```
GET /api/questionarios/listar
```

**Filtros disponíveis:**
- `sexo` - F, M, O
- `faixa_etaria` - 18-29, 30-39, 40-49, 50-59, 60+
- `estado` - Sigla do estado
- `cidade` - Nome da cidade
- `data_inicio` - Data início (YYYY-MM-DD)
- `data_fim` - Data fim (YYYY-MM-DD)
- `status_tabagismo` - Nunca, Ex-fumante, Sim
- `tem_sinais_alerta` - true/false
- `progresso_minimo` - Percentual mínimo (0-100)
- `page` - Página (padrão: 1)
- `per_page` - Itens por página (padrão: 15)
- `sort_by` - Campo para ordenação (padrão: data_preenchimento)
- `sort_direction` - asc/desc (padrão: desc)

**Exemplo:**
```
GET /api/questionarios/listar?sexo=F&progresso_minimo=50&page=1&per_page=10&sort_by=nome_completo&sort_direction=asc
```

**Resposta:**
```json
{
  "sucesso": true,
  "questionarios": {
    "data": [
      {
        "id": 1,
        "usuario_id": 1,
        "nome_completo": "Maria Silva",
        "data_preenchimento": "2024-01-15T10:30:00Z",
        "sexo_biologico": "F",
        "idade": 45,
        "estado": "SP",
        "cidade": "São Paulo",
        "progresso": {
          "campos_preenchidos": 25,
          "campos_totais": 33,
          "percentual": 75.8,
          "status": "avancado"
        },
        "tem_sinais_alerta": false,
        "status_tabagismo": "Nunca",
        "consome_alcool": true,
        "pratica_atividade": true
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 10,
      "total": 50,
      "from": 1,
      "to": 10
    }
  }
}
```

---

## 🔐 **AUTENTICAÇÃO**

Todos os endpoints requerem autenticação Firebase:

```http
Authorization: Bearer {firebase_token}
```

---

## 📊 **DADOS FICTÍCIOS**

✅ **Seeder executado com sucesso!**
- **10 usuários** criados automaticamente
- **15 questionários** completos gerados
- **5 questionários** parciais para teste
- **Dados realistas** com distribuição geográfica brasileira
- **Fatores de risco** variados e realistas

---

## 🎯 **PRÓXIMOS PASSOS**

1. **Frontend Dashboard** - Criar interface para visualizar os dados
2. **Gráficos Interativos** - Implementar charts com Chart.js ou similar
3. **Filtros Avançados** - Interface de filtros dinâmicos
4. **Exportação** - PDF/Excel dos relatórios
5. **Alertas** - Notificações para sinais de alerta

---

## 🚀 **SISTEMA PRONTO!**

O backend está 100% funcional com:
- ✅ **8 endpoints analíticos** completos
- ✅ **Filtros avançados** em todos os endpoints
- ✅ **Dados fictícios** populados
- ✅ **Autenticação** Firebase implementada
- ✅ **Paginação** e ordenação
- ✅ **Análises estatísticas** detalhadas

**Pronto para criar o dashboard frontend! 🎉**
