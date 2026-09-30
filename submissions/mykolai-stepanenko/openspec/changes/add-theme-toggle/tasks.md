# Tasks

## 1. Tailwind class-стратегія та ініціалізація теми

- [ ] 1.1 `laravel-tester` (red) додає feature-тест: `GET /` (з `withoutVite()`) містить inline `<script id="theme-init">` у `<head>`, розташований перед `<div id="app">`. Перевірка: тест червоний з очікуваної причини.
- [ ] 1.2 Додати `@custom-variant dark (&:where(.dark, .dark *));` в `resources/css/app.css` і inline-скрипт `#theme-init` у `<head>` `app.blade.php` перед `@vite` (`localStorage.theme`, інакше `prefers-color-scheme`, у `try/catch`). Перевірка: тест з 1.1 зелений, `npm run build` успішний.

## 2. Перемикач теми у Vue

- [ ] 2.1 Створити `resources/js/composables/useTheme.js`: `isDark` (з класу `<html>`) і `toggleTheme()` (клас + `localStorage` у `try/catch`). Перевірка: `npm run build` успішний.
- [ ] 2.2 Створити `resources/js/components/ThemeToggle.vue` (кнопка, SVG сонце або місяць, `aria-label` за станом) і розмістити в `App.vue` у правому верхньому куті. Перевірка: `npm run build` успішний, кнопка видна на http://localhost:8080.

## 3. Інтеграційна перевірка

- [ ] 3.1 `laravel-tester` (verify): увесь набір `php artisan test --compact` зелений, Larastan `[OK]`, Pint без порушень.
- [ ] 3.2 Ручний smoke у браузері: перемикач міняє тему туди й назад; після перезавантаження тема зберігається; очищений `localStorage` з темною ОС дає темну тему; немає блимання при перезавантаженні; консоль без помилок.
