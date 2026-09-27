# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Context

This directory (`submissions/mykolai-stepanenko/`) is one participant's capstone submission inside a fork of the fwdays "Crash Course: Agentic Engineering" capstone repo. Course rules are in `../../README.md` and `../../RUBRIC.md`:

- Do not modify course files at the repo root (`README.md`, `RUBRIC.md`, `.github/`, `templates/`). All work stays inside this directory so the PR diff shows only this submission.
- Every agentic practice claimed (context engineering, loops, verification, maker ≠ checker, SDD) must be backed by something inspectable — a file, commit, test, or run log. `../../templates/autonomy-log.md` is the log template.

## Stack

Laravel 13 on PHP 8.5, MySQL 8.4, Redis 8, nginx — all in Docker (`compose.yaml`). There is no PHP/Composer on the host; run every `php`/`composer`/`artisan` command inside the `app` container. The `AGENTS.md` shipped by the Laravel skeleton suggests installing PHP on the host — ignore that part here.

- `app` — php-fpm 8.5 built from `.docker/php/Dockerfile` (Composer included; runs as `www-data` remapped to the host UID/GID so files in the bind mount stay owned by you). OPcache is built into PHP 8.5, so it is not installed as an extension.
- `nginx` — serves `public/`, proxies PHP to `app:9000` (`.docker/nginx/default.conf`, resolves `app` at request time so recreating `app` doesn't cause 502). App at http://localhost:8080 (`APP_PORT`).
- `mysql` — MySQL 8.4, host port 33060 (`FORWARD_DB_PORT`); `.env` uses `DB_HOST=mysql`, db/user `laravel`/`laravel`, password `secret`.
- `redis` — Redis 8 (`redis:8-alpine`), host port 63790 (`FORWARD_REDIS_PORT`); PHP uses the `phpredis` extension, `.env` has `REDIS_HOST=redis`.

## Commands

Export `UID`/`GID` first so the image is built with your user IDs (zsh's `UID` is read-only but already set, so just `export UID GID=$(id -g)`):

```sh
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app php artisan test                          # all tests
docker compose exec app php artisan test --filter=ExampleTest     # single test
docker compose exec app vendor/bin/phpstan analyse --memory-limit=2G   # Larastan
docker compose exec app composer require <package>
```

Larastan config is `phpstan.neon` (level 5, paths `app/`). Don't lower the level or add ignores to make analysis pass — fix the code.

## PHP conventions

When writing PHP, invoke the `php-minimal-phpdoc` skill: PHPDoc only for types the language can't express (array shapes, generics); no prose docblocks, no restating native type hints.
