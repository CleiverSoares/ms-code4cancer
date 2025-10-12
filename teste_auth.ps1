# Teste de autenticação Firebase
# Simular token Firebase para teste

$tokenSimulado = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL3NlY3VyZXRva2VuLmdvb2dsZWFwaS5jb20vc29maWEtMTRmMTkiLCJhdWQiOiJzb2ZpYS0xNGYxOSIsImF1dGhfdGltZSI6MTY5NzEzMjAwMCwidXNlcl9pZCI6Ik96c3BuRVRneE9QNVJaRjd0UmJCSk9lWXBqODMiLCJzdWIiOiJPenNwbkVUZ3hPUDVSWkY3dFJiQkpPZVlwajgzIiwiaWF0IjoxNjk3MTMyMDAwLCJleHAiOjE2OTcxMzU2MDAsImVtYWlsIjoiY2xlaXZlcnNvYXJlczJAZ21haWwuY29tIiwiZW1haWxfdmVyaWZpZWQiOnRydWUsImZpcmViYXNlIjp7ImlkZW50aXRpZXMiOnsiZW1haWwiOlsiY2xlaXZlcnNvYXJlczJAZ21haWwuY29tIl19LCJzaWduX2luX3Byb3ZpZGVyIjoiZ29vZ2xlLmNvbSJ9.test-signature"

Write-Host "=== TESTE DE AUTENTICAÇÃO FIREBASE ===" -ForegroundColor Green

# Teste 1: Endpoint sem token
Write-Host "`n1. Testando endpoint sem token:" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/questionarios/teste-simples" -Method GET
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Red
    Write-Host "Resposta: $($response.Content)" -ForegroundColor Red
} catch {
    Write-Host "Erro esperado: $($_.Exception.Message)" -ForegroundColor Red
}

# Teste 2: Endpoint com token simulado
Write-Host "`n2. Testando endpoint com token simulado:" -ForegroundColor Yellow
try {
    $headers = @{
        'Authorization' = "Bearer $tokenSimulado"
        'Content-Type' = 'application/json'
    }
    
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/questionarios/teste-simples" -Method GET -Headers $headers
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Resposta: $($response.Content)" -ForegroundColor Green
} catch {
    Write-Host "Erro: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Resposta do servidor: $responseBody" -ForegroundColor Red
    }
}

# Teste 3: Teste de questionário completo
Write-Host "`n3. Testando salvamento de questionário:" -ForegroundColor Yellow

$dadosQuestionario = @{
    nomeCompleto = "Teste Usuario"
    dataNascimento = "1990-01-01"
    sexoBiologico = "M"
    cidade = "São Paulo"
    estado = "SP"
    precisaAtendimentoPrioritario = $true
    sangramentoAnormal = $true
    tossePersistente = $true
} | ConvertTo-Json

try {
    $headers = @{
        'Authorization' = "Bearer $tokenSimulado"
        'Content-Type' = 'application/json'
    }
    
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/questionarios" -Method POST -Headers $headers -Body $dadosQuestionario
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Resposta: $($response.Content)" -ForegroundColor Green
} catch {
    Write-Host "Erro: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Resposta do servidor: $responseBody" -ForegroundColor Red
    }
}

Write-Host "`n=== TESTE CONCLUÍDO ===" -ForegroundColor Green
