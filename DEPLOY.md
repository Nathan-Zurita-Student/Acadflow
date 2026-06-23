# 🚀 Colinha de Deploy — AcadFlow

Guia rápido de tudo que precisa rodar ao subir uma atualização, e por que as
fotos de perfil quebravam a cada deploy.

---

## TL;DR (o que fazer)

No **Laravel Cloud**, configure o **Deploy Command** do ambiente para:

```bash
php artisan app:deploy
```

Pronto. A partir daí **você nunca mais precisa rodar `php artisan storage:link` na mão.**
O `app:deploy` já faz isso (e mais) automaticamente a cada deploy.

> Onde fica: painel do Laravel Cloud → seu **Environment** → **Settings / Deployments**
> → campo **Deploy Command** (também chamado de "deployment hook" / comando pós-build).

---

## Por que as imagens de perfil sumiam?

1. Os avatares são salvos no disco `public` (`storage/app/public/avatars/...`) e a URL
   gerada é tipo `/storage/avatars/foto.jpg`.
2. Esse caminho só funciona se existir o **link simbólico** `public/storage` →
   `storage/app/public`. Esse link é criado pelo comando `php artisan storage:link`.
3. O link **não vai para o Git** (está no `.gitignore`) e o sistema de arquivos do
   Laravel Cloud é **efêmero**: ele é recriado do zero a cada deploy. Logo, o link
   some e as imagens passam a dar 404 — até você rodar `storage:link` de novo.

**Isso é normal em projetos profissionais?**
Rodar `storage:link` é normal — mas **na mão, não**. Em produção ele entra no
comando de deploy (é o que estamos fazendo aqui). E, para uploads de usuário, o
padrão profissional é guardar os arquivos em **object storage** (ver a seção
"Solução definitiva" mais abaixo), o que faz o problema do symlink desaparecer.

---

## O que o `php artisan app:deploy` faz

Veja em [`app/Console/Commands/DeployCommand.php`](app/Console/Commands/DeployCommand.php).
Ele roda, em ordem:

| Passo | Comando | Para quê |
|------|---------|----------|
| 1 | `migrate --force` | aplica migrations pendentes no banco |
| 2 | `storage:link --force` | **recria o link público** (resolve as fotos) |
| 3 | `config:cache` | cacheia configs (mais performance) |
| 4 | `view:cache` | cacheia as views |
| 5 | `queue:restart` | reinicia os workers de fila com o código novo |

> `route:cache` é intencionalmente omitido porque `routes/web.php` usa uma rota
> com closure (catch-all do Inertia), que não pode ser cacheada.

---

## Colinha de comandos

### Subir atualização no Laravel Cloud
Nada manual: faça o `git push` para a branch do ambiente. O build roda
`composer install`, `npm run build` e, no fim, o **Deploy Command** (`app:deploy`).

### Deploy manual (VPS / Docker / servidor próprio)
```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan app:deploy        # migrations + storage:link + caches + queue restart
```

### Comandos avulsos úteis
```bash
php artisan storage:link --force   # recria só o link público do storage
php artisan migrate --force        # roda migrations sem perguntar (produção)
php artisan optimize:clear         # limpa TODOS os caches (config/route/view/event)
php artisan config:cache           # recacheia config
php artisan queue:work             # processa a fila (workers)
php artisan reverb:start           # servidor de websockets (tempo real)
```

### Rodar localmente (desenvolvimento)
```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run dev          # Vite (frontend)
php artisan serve    # backend
```

---

## ✅ Solução definitiva (recomendada): object storage

Como o disco do Laravel Cloud é efêmero, mesmo com o `storage:link` rodando a cada
deploy os **arquivos enviados podem se perder** entre deploys (o link aponta para
uma pasta que foi recriada vazia). A correção profissional é **não guardar uploads
no disco local** e sim em um **bucket S3-compatível** (o Laravel Cloud provisiona
um para você).

Passo a passo (quando quiser fazer):

1. No Laravel Cloud, crie/conecte um **bucket de object storage**.
2. No `.env` do ambiente, defina:
   ```env
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=...
   AWS_SECRET_ACCESS_KEY=...
   AWS_DEFAULT_REGION=...
   AWS_BUCKET=...
   AWS_URL=...            # URL pública do bucket
   AWS_USE_PATH_STYLE_ENDPOINT=false
   ```
3. Garanta que os uploads usem o disco padrão. Hoje o código fixa o disco `public`
   (ex.: `->store('avatars', 'public')` em
   [`AuthController`](app/Http/Controllers/Api/AuthController.php) e
   [`AttachmentController`](app/Http/Controllers/Api/AttachmentController.php)).
   Trocar `'public'` por `config('filesystems.default')` (ou `'s3'`) faz os arquivos
   irem para o bucket e **persistirem** entre deploys — e aí o `storage:link` deixa
   de ser necessário para eles.

> Enquanto não migrar para object storage, o `app:deploy` no Deploy Command já
> resolve o sintoma (link recriado) e mantém as fotos aparecendo após cada deploy.
