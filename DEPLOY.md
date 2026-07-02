# 🚀 Deploy do AcadFlow no Laravel Cloud — do zero (passo a passo)

Guia completo para recriar o projeto no Laravel Cloud com **tudo configurado
corretamente**, na ordem certa. Siga de cima para baixo.

---

## O que o AcadFlow precisa (resumo)

| Recurso | Precisa? | Observação |
|--------|----------|------------|
| PHP 8.3+ | ✅ | exigido pelo `composer.json` |
| Node (build do Vite) | ✅ | `npm run build` gera o front |
| Banco **MySQL** | ✅ | migrations + dados |
| **Object Storage (S3)** | ✅ | **uploads persistentes** (fotos/anexos) — o que vinha falhando |
| Worker de fila | ❌ | nada usa fila (eventos são `ShouldBroadcastNow`) |
| Agendador (cron) | ❌ | não há tarefas agendadas |
| Reverb (tempo real) | ⚪ opcional | sem ele, o app usa *polling* automaticamente |
| E-mail (SMTP) | ⚪ opcional | só se for usar recuperação de senha por e-mail |

---

## Passo 0 — Antes de apagar o projeto atual

Guarde os segredos que você vai reaproveitar (vão para as variáveis do projeto novo):

- `APP_KEY` (pode reusar a atual ou gerar outra)
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
- `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL`
- `ASAAS_API_KEY`, `ASAAS_BASE_URL`, `ASAAS_WEBHOOK_TOKEN`, `PLAN_GRACE_DAYS`

> Se houver dados em produção que importam, exporte o banco antes. Se é recomeço
> limpo, pode ignorar.

---

## Passo 1 — Apagar o projeto antigo

No painel do Laravel Cloud → projeto antigo → **Settings** → **Delete project**.
Apaga junto os recursos (banco/bucket antigos) para não pagar/confundir.

---

## Passo 2 — Criar o projeto novo e conectar o Git

1. **New Project** (ou New Application).
2. Conecte o **repositório GitHub** do AcadFlow.
3. Branch de produção: **`main`**.
4. Ambiente: **production**.
5. **Não faça deploy ainda** — primeiro vamos criar banco, storage e variáveis.

---

## Passo 3 — Banco de dados (MySQL)

1. No projeto → seção de **Database** → **Create Database** → tipo **MySQL**.
2. **Conecte ao ambiente** (production).
3. O Laravel Cloud injeta sozinho: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`,
   `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. (Não precisa digitar.)

---

## Passo 4 — Object Storage (uploads) — **do jeito do Laravel Cloud**

É isto que faz as fotos de perfil/anexos **persistirem** entre deploys.

> ⚠️ **Importante:** no Laravel Cloud você **NÃO** usa o disco `s3` nem variáveis
> `AWS_*`. O Laravel Cloud provisiona um Object Storage (Cloudflare R2) e o liga
> **automaticamente no disco `public`** através da variável `LARAVEL_CLOUD_DISK_CONFIG`
> (ele mesmo injeta essa variável). O disco `public` deixa de ser local e passa a
> ser o R2 persistente — sem você configurar nada de credencial.

1. No projeto → seção **Storage** → **crie/anexe um Object Storage** ao ambiente.
2. O Laravel Cloud injeta sozinho `LARAVEL_CLOUD_DISK_CONFIG` e deixa
   `FILESYSTEM_DISK=public`. Não precisa mexer em `AWS_*`.
3. Como o app usa o disco **`public`** para uploads (padrão), os arquivos já vão
   para o R2 e **persistem**.

> ✅ **Use `UPLOAD_DISK=public`** (ou **não** defina essa variável — o padrão já é
> `public`). **NÃO** use `UPLOAD_DISK=s3` — é isso que gera o erro
> *"region is required"* (o disco `s3` fica sem credenciais, porque o Cloud
> configura o `public`, não o `s3`).
>
> Como conferir: nas variáveis deve existir `LARAVEL_CLOUD_DISK_CONFIG` (com um
> `bucket` e uma `url` tipo `https://fls-....laravel.cloud`).

---

## Passo 5 — Variáveis de ambiente

Em **Environment Variables** do ambiente, garanta o conjunto abaixo.
As marcadas como *(automática)* já vêm dos Passos 3 e 4 — **não duplique**.

```env
# App
APP_NAME=AcadFlow
APP_ENV=production
APP_KEY=base64:COLE_SUA_CHAVE
APP_DEBUG=false
APP_URL=https://SEU-DOMINIO.laravel.cloud      # ajuste no Passo 8

# Banco (automática — vem do MySQL conectado)
DB_CONNECTION=mysql

# Sessão / cache / fila (nada usa fila de fato)
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Tempo real DESLIGADO (simples e estável). App cai p/ polling sozinho.
# ⚠️ NÃO use "reverb" sem ter o servidor Reverb configurado — quebra o build.
BROADCAST_CONNECTION=null

# Uploads → disco "public" (que o Laravel Cloud liga no R2 automaticamente).
# NÃO use "s3" aqui — dá erro de "region". Pode até omitir (o padrão é public).
UPLOAD_DISK=public
# LARAVEL_CLOUD_DISK_CONFIG e FILESYSTEM_DISK=public vêm automáticos do Cloud.

# Google OAuth (atualize o REDIRECT no Passo 8)
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://SEU-DOMINIO.laravel.cloud/api/auth/google/callback

# IA (Anthropic)
ANTHROPIC_API_KEY=...
ANTHROPIC_MODEL=claude-haiku-4-5

# Pagamentos (Asaas)
ASAAS_API_KEY=...
ASAAS_BASE_URL=https://api.asaas.com/v3
ASAAS_WEBHOOK_TOKEN=...
PLAN_GRACE_DAYS=3
```

> **APP_KEY:** se o Laravel Cloud não gerar sozinho, rode local
> `php artisan key:generate --show` e cole o valor (começa com `base64:`).
>
> **Opcionais:** `APP_TIMEZONE=America/Sao_Paulo` e `APP_LOCALE=pt_BR` se quiser
> horários/idioma do Brasil.

---

## Passo 6 — Build e Deploy Command

1. **Build** (o Laravel Cloud detecta Laravel + `package.json` e roda sozinho):
   `composer install --no-dev` e `npm ci && npm run build`. Só confirme que o
   build do front (`npm run build`) está ativado.
2. **Deploy Command** do ambiente — coloque **exatamente**:
   ```bash
   php artisan app:deploy
   ```
   Isso roda migrations + `storage:link` + caches a cada deploy (ver apêndice).
3. **PHP version:** 8.3 (ou superior).

> O `Procfile`/`Dockerfile` do repositório **não** são usados pelo Laravel Cloud —
> pode ignorá-los.

---

## Passo 7 — Primeiro deploy

1. Dispare o **deploy** (botão Deploy ou um `git push` na branch `main`).
2. Acompanhe os **Logs** do deploy. Você deve ver o `app:deploy` rodar:
   `Migrations → Storage link → Config cache → View cache → Queue restart`.
3. Se falhar, leia a primeira linha do erro nos **Logs** (não o “Server Error”
   genérico da tela).

---

## Passo 8 — Domínio + Google OAuth + Asaas

Com a URL final do ambiente (ex.: `https://acadflow-xxxx.laravel.cloud` ou seu
domínio próprio):

1. Ajuste **`APP_URL`** para essa URL.
2. Ajuste **`GOOGLE_REDIRECT_URI`** para `…/api/auth/google/callback`.
3. No **Google Cloud Console** → Credenciais → seu OAuth Client → adicione essa
   URL em **Authorized redirect URIs** (senão o login Google dá erro de redirect).
4. No **Asaas** → webhook → aponte para a nova URL (se você usa webhook de
   pagamento).
5. Redeploy para aplicar as variáveis alteradas.

---

## Passo 9 — Checklist de verificação (pós-deploy)

- [ ] Abre a home, faz **login** (e login Google, se usa).
- [ ] **Cria um projeto** e uma **tarefa**.
- [ ] **Troca a foto de perfil** → a imagem aparece. ✅
- [ ] Faz um novo deploy de teste → a foto **continua aparecendo** (persistiu!). ✅
- [ ] Upload de **arquivo** num projeto → abre/baixa normalmente.
- [ ] Calendário carrega as tarefas.

Se a foto subir mas **não exibir** (erro 403 na imagem), o bucket está privado →
me avise para eu trocar para servir via aplicação.

---

## (Opcional) Tempo real com Reverb

O app funciona 100% sem isso (usa polling). Para ligar atualizações ao vivo:

1. No Laravel Cloud, habilite **Reverb** para o ambiente (ele provisiona o
   servidor de WebSocket e injeta `REVERB_APP_ID/KEY/SECRET/HOST/PORT/SCHEME`).
2. Troque `BROADCAST_CONNECTION=null` por `BROADCAST_CONNECTION=reverb`.
3. Defina as variáveis de **build** do front (precisam existir ANTES do build):
   ```env
   VITE_REVERB_APP_KEY=${REVERB_APP_KEY}
   VITE_REVERB_HOST=${REVERB_HOST}
   VITE_REVERB_PORT=443
   VITE_REVERB_SCHEME=https
   ```
4. Redeploy (para o front ser reconstruído com as `VITE_*`).

> Sem essas `VITE_*`, o front não tenta conectar e cai em polling — sem erros.

---

## Apêndice A — O que o `php artisan app:deploy` faz

Ver [app/Console/Commands/DeployCommand.php](app/Console/Commands/DeployCommand.php):

| Passo | Comando | Para quê |
|------|---------|----------|
| 1 | `migrate --force` | aplica migrations |
| 2 | `storage:link --force` | link público do storage (disco local) |
| 3 | `config:cache` | cacheia config |
| 4 | `view:cache` | cacheia views |
| 5 | `queue:restart` | reinicia workers (inofensivo se não houver) |

> `route:cache` é omitido de propósito (a rota catch-all do Inertia usa closure).

## Apêndice B — Por que os uploads precisam do Object Storage

O sistema de arquivos do contêiner do Laravel Cloud é **efêmero** (recriado a cada
deploy). No disco local, os arquivos enviados somem. No Laravel Cloud, ao anexar um
Object Storage, ele liga o disco **`public`** ao R2 (via `LARAVEL_CLOUD_DISK_CONFIG`),
que **persiste**. O app usa `config('filesystems.uploads')` (padrão `public`) em
[config/filesystems.php](config/filesystems.php), então basta manter
`UPLOAD_DISK=public`. Referências:
[AuthController](app/Http/Controllers/Api/AuthController.php) (avatar, via `store()`
sem ACL — compatível com R2) e
[AttachmentController](app/Http/Controllers/Api/AttachmentController.php).

> Fora do Laravel Cloud (ex.: AWS S3 próprio), aí sim você usaria `UPLOAD_DISK=s3`
> com as variáveis `AWS_*` do seu bucket.

## Apêndice C — Deploy manual (VPS/local), fora do Laravel Cloud

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan app:deploy
```
