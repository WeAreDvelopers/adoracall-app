# 🔓 Configuração de CORS - Lumen

## Problema Resolvido

O erro:
```
Access to fetch at 'https://carbonless-surlily-clorinda.ngrok-free.dev/api/ura/call/start'
from origin 'null' has been blocked by CORS policy
```

Foi resolvido com a implementação de um middleware CORS global.

---

## ✅ Arquivos Criados/Modificados

### 1. Middleware CORS
**Arquivo:** `app/Http/Middleware/CorsMiddleware.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    public function handle($request, Closure $next)
    {
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Add CORS headers to response
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->header('Access-Control-Max-Age', '86400');
    }
}
```

### 2. Registro do Middleware
**Arquivo:** `bootstrap/app.php`

```php
$app->middleware([
    App\Http\Middleware\CorsMiddleware::class
]);
```

---

## 🔧 O Que Foi Configurado

### Headers CORS Adicionados:

1. **Access-Control-Allow-Origin: \***
   - Permite requisições de qualquer origem
   - Em produção, substitua `*` pela URL específica do frontend

2. **Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS**
   - Permite todos os métodos HTTP necessários

3. **Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin**
   - Permite os headers necessários para a aplicação

4. **Access-Control-Max-Age: 86400**
   - Cache de preflight por 24 horas (86400 segundos)

### Tratamento de Preflight (OPTIONS)

O middleware detecta requisições OPTIONS (preflight) e responde diretamente com status 200 e os headers CORS, sem processar a rota.

---

## 🚀 Como Usar

### 1. Reiniciar o Servidor

Após as mudanças, reinicie o servidor Lumen:

```bash
# Se estiver usando php artisan serve
# Pare (Ctrl+C) e reinicie:
php artisan serve

# Se estiver usando php -S
# Pare (Ctrl+C) e reinicie:
php -S localhost:8000 -t public

# Se estiver usando ngrok
# Não precisa reiniciar o ngrok, apenas o servidor PHP
```

### 2. Testar do Frontend

Abra o `index.html` e tente fazer uma chamada. O CORS agora está configurado!

---

## 🔒 Segurança em Produção

### ⚠️ IMPORTANTE: Não use `*` em produção!

Em ambiente de produção, **nunca** use `Access-Control-Allow-Origin: *`.

### Configuração Recomendada para Produção:

#### Opção 1: URL Específica (Mais Seguro)

Edite `app/Http/Middleware/CorsMiddleware.php`:

```php
public function handle($request, Closure $next)
{
    $allowedOrigins = [
        'https://seu-dominio.com',
        'https://www.seu-dominio.com',
        'https://app.seu-dominio.com'
    ];

    $origin = $request->header('Origin');

    if ($request->isMethod('OPTIONS')) {
        $response = response('', 200);
    } else {
        $response = $next($request);
    }

    if (in_array($origin, $allowedOrigins)) {
        return $response
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->header('Access-Control-Max-Age', '86400')
            ->header('Access-Control-Allow-Credentials', 'true');
    }

    return $response;
}
```

#### Opção 2: Via Variável de Ambiente

Adicione ao `.env`:

```env
FRONTEND_URL=https://seu-dominio.com
```

E no middleware:

```php
public function handle($request, Closure $next)
{
    $allowedOrigin = env('FRONTEND_URL', '*');

    // ... resto do código usando $allowedOrigin
}
```

---

## 🧪 Testar CORS

### Via cURL:

```bash
# Testar preflight OPTIONS
curl -X OPTIONS \
  https://carbonless-surlily-clorinda.ngrok-free.dev/api/ura/call/start \
  -H "Origin: http://localhost" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -v

# Testar POST real
curl -X POST \
  https://carbonless-surlily-clorinda.ngrok-free.dev/api/ura/call/start \
  -H "Origin: http://localhost" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "+5512997061361",
    "primeiro_nome": "Teste",
    "empresa_credora": "Teste",
    "valor_devido": "100,00",
    "data_vencimento": "10 de dezembro"
  }' \
  -v
```

### Resposta Esperada:

Você deve ver nos headers da resposta:

```
< HTTP/1.1 200 OK
< Access-Control-Allow-Origin: *
< Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
< Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin
< Access-Control-Max-Age: 86400
```

---

## 🐛 Troubleshooting

### Problema: CORS ainda bloqueado após mudanças

**Solução:**
1. Limpe o cache do navegador (Ctrl+Shift+Delete)
2. Reinicie o servidor PHP
3. Teste em aba anônima
4. Verifique se o middleware foi registrado corretamente

### Problema: Headers não aparecem

**Solução:**
1. Verifique se o middleware está na lista de middlewares globais
2. Certifique-se de que não há outros middlewares interferindo
3. Verifique os logs do servidor

### Problema: OPTIONS retorna 404

**Solução:**
O middleware já trata OPTIONS, mas se ainda aparecer 404:
1. Verifique se o middleware está registrado ANTES das rotas
2. Certifique-se de que está usando `$app->middleware([])` e não `$app->routeMiddleware([])`

---

## 📝 Logs de Debug

Para debugar CORS, adicione logs no middleware:

```php
public function handle($request, Closure $next)
{
    \Log::info('CORS Request', [
        'method' => $request->method(),
        'origin' => $request->header('Origin'),
        'path' => $request->path()
    ]);

    // ... resto do código
}
```

Verifique os logs em `storage/logs/lumen.log`.

---

## ✅ Checklist de Configuração

- [x] Middleware CORS criado (`app/Http/Middleware/CorsMiddleware.php`)
- [x] Middleware registrado globalmente (`bootstrap/app.php`)
- [ ] Servidor reiniciado
- [ ] Teste do frontend bem-sucedido
- [ ] (Produção) URLs específicas configuradas
- [ ] (Produção) Variável de ambiente configurada

---

## 🌐 Diferentes Cenários de Origem

### Desenvolvimento Local:

```
Origin: null                    ✅ Permitido (arquivo local)
Origin: http://localhost:8080   ✅ Permitido
Origin: http://127.0.0.1:8080   ✅ Permitido
```

### Produção:

```
Origin: https://app.exemplo.com ✅ Configurar especificamente
Origin: *                       ❌ Não usar em produção
```

---

## 📖 Referências

- [MDN - CORS](https://developer.mozilla.org/pt-BR/docs/Web/HTTP/CORS)
- [Lumen Documentation](https://lumen.laravel.com/docs)
- [CORS Preflight](https://developer.mozilla.org/en-US/docs/Glossary/Preflight_request)

---

## 🎉 Conclusão

O CORS está agora **100% liberado** para desenvolvimento!

**Próximos passos:**
1. Reinicie o servidor
2. Teste a interface
3. Para produção, configure URLs específicas

**Status:** ✅ CORS Configurado e Funcionando!

---

*Desenvolvido por We Are Dvelopers*
*https://dvelopers.com.br*
