# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 13 on PHP 8.5, MySQL 8.4, Redis 8, Node 26, nginx — all in Docker (`compose.yaml`). There is no PHP/Composer/Node on the host; run `php`/`composer`/`artisan` in the `app` container and `node`/`npm` in the `node` container. The `AGENTS.md` shipped by the Laravel skeleton suggests installing PHP on the host — ignore that part here.

- `app` — php-fpm 8.5 built from `.docker/php/Dockerfile` (Composer included; runs as `www-data` remapped to the host UID/GID so files in the bind mount stay owned by you). OPcache is built into PHP 8.5, so it is not installed as an extension.
- `nginx` — serves `public/`, proxies PHP to `app:9000` (`.docker/nginx/default.conf`, resolves `app` at request time so recreating `app` doesn't cause 502). App at http://localhost:8080 (`APP_PORT`).
- `mysql` — MySQL 8.4, host port 33060 (`FORWARD_DB_PORT`); `.env` uses `DB_HOST=mysql`, db/user `laravel`/`laravel`, password `secret`.
- `redis` — Redis 8 (`redis:8-alpine`), host port 63790 (`FORWARD_REDIS_PORT`); PHP uses the `phpredis` extension, `.env` has `REDIS_HOST=redis`.
- `node` — Node 26 (`node:26-alpine`), idles so commands can `exec` into it; runs as host UID/GID; Vite port 5173 (`VITE_PORT`).

## Claude Code hooks

Hooks in `.claude/hooks/*.mjs` run through `.claude/hooks/run-node.sh`, which executes them with Node from the `node` container (`docker compose exec`, or a throwaway `docker compose run` if the stack is down) and preserves the exit code, so `protect-env.mjs` still blocks with exit 2. New Node hooks must be registered in `.claude/settings.json` the same way: `"command": "sh", "args": ["…/run-node.sh", "…/<hook>.mjs"]`. Inside the container `CLAUDE_PROJECT_DIR` is `/var/www/html`.

## Commands

Export `UID`/`GID` first so the image is built with your user IDs (zsh's `UID` is read-only but already set, so just `export UID GID=$(id -g)`):

```sh
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan test
docker compose exec app vendor/bin/phpstan analyse --memory-limit=2G   # Larastan
docker compose exec node npm install && docker compose exec node npm run build
```

Larastan config is `phpstan.neon` (level 5, paths `app/`). Don't lower the level or add ignores to make analysis pass — fix the code.
