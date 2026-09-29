# План: ціна BTC в UAH з двома провайдерами (fallback) + HTTP endpoint

## Context
Потрібен сервіс, що повертає поточну ціну Bitcoin у гривнях. Дані беруться з двох безкоштовних API за спільним інтерфейсом. Якщо перший провайдер не відповів або повернув некоректні дані, сервіс звертається до другого. Ціну видає `GET /api/btc-price`. Реалізація за SOLID/DRY/KISS/YAGNI, у межах правила шарів `.claude/rules/services-layered-architecture.md`, через TDD з `laravel-tester`.

## Провайдери (перевірено curl, обидва без ключа)
| Провайдер | URL | Поле | Приклад |
|---|---|---|---|
| CoinGecko (основний) | `https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=uah` | `bitcoin.uah` | `3793253` |
| Coinbase (fallback) | `https://api.coinbase.com/v2/prices/BTC-UAH/spot` | `data.amount` | `"3788336.34…"` |

Відкинуті варіанти: CryptoCompare вимагає ключ, у Kraken немає пари UAH. У Binance і WhiteBIT пара BTC/UAH є, але це біржові ціни з помітно іншим курсом.

## Структура
```
app/Services/BitcoinPrice/
  Contracts/BitcoinPriceProvider.php   interface: name(): string; fetchUahPrice(): float
  Providers/HttpBitcoinPriceProvider.php  abstract: GET url() з timeout, ->throw(), extractPrice(array $json);
                                          перевіряє, що ціна числова і > 0, інакше BitcoinPriceUnavailableException (DRY для обох)
  Providers/CoinGeckoProvider.php      url() + extractPrice() = data_get($json, 'bitcoin.uah')
  Providers/CoinbaseProvider.php       url() + extractPrice() = data_get($json, 'data.amount')
  BitcoinPrice.php                     final readonly DTO: float $amount, string $provider
  BitcoinPriceService.php              current(): BitcoinPrice — провайдери по черзі, перший успішний
app/Exceptions/BitcoinPriceUnavailableException.php
app/Http/Controllers/Api/BitcoinPriceController.php  invokable → JSON
routes/api.php + реєстрація `api:` у bootstrap/app.php
```

- **SOLID:**
  - SRP: провайдер лише отримує ціну з одного API, сервіс лише керує fallback, контролер лише формує HTTP-відповідь.
  - OCP/DIP: щоб додати третій провайдер, достатньо нового класу і рядка в конфігу. Сервіс залежить тільки від інтерфейсу.
  - LSP/ISP: інтерфейс має два методи.
- **HTTP-клієнт:** провайдери отримують `Illuminate\Http\Client\Factory` через конструктор, без фасаду `Http`, як вимагає правило. У тестах `Http::fake()` усе одно працює, бо фасад використовує той самий Factory.
- **Сервіс:** отримує `list<BitcoinPriceProvider>` і `Psr\Log\LoggerInterface`. Коли провайдер не спрацював, пише warning у лог і пробує наступного. Якщо не спрацював жоден, кидає `BitcoinPriceUnavailableException`. Вимоги правила виконано: без HTTP-відповідей, без БД, помилки через доменний exception.
- **Конфіг** `config/services.php` → `'bitcoin_price' => ['providers' => [CoinGeckoProvider::class, CoinbaseProvider::class], 'timeout' => 5]`. Порядок у масиві визначає пріоритет.
- **Прив'язка** в `app/Providers/AppServiceProvider::register()`: `$this->app->when(BitcoinPriceService::class)->needs('$providers')->give(fn ($app) => array_map($app->make(...), config(...)))`.
- **Endpoint:** `GET /api/btc-price`.
  - Успіх: `200 {"price": 3793253.0, "currency": "UAH", "provider": "coingecko"}`.
  - Жоден провайдер не відповів: `503 {"message": "Bitcoin price is temporarily unavailable."}`. Exception перетворюється на 503 у контролері.
  - `routes/api.php` підключається через `withRouting(api: …)` без `install:api`, тобто без Sanctum і без нових залежностей.
- **YAGNI:** без кешу, без розрахунку середнього, без DTO валюти, бо валюта завжди UAH. Ціна зберігається як `float`, бо вона лише для відображення, розрахунків з нею немає.

## Кроки (TDD)
1. **Red — `laravel-tester`** пише тести:
   - `tests/Unit/Services/BitcoinPriceServiceTest.php` з фейковими реалізаціями інтерфейсу:
     - перший провайдер успішний, тоді другий не викликається;
     - перший кидає exception, тоді ціну дає другий, `provider` = другий;
     - обидва не спрацювали, тоді `BitcoinPriceUnavailableException`.
   - `tests/Feature/BitcoinPrice/ProvidersTest.php`, де `Http::fake` + `Http::preventStrayRequests()`, для кожного провайдера:
     - коректна відповідь → ціна;
     - 500, некоректний JSON, `0` або нечислове значення → exception.
   - `tests/Feature/BitcoinPriceEndpointTest.php`:
     - 200 зі структурою JSON;
     - fallback на Coinbase, коли CoinGecko повертає 500;
     - 503, коли не відповідає жоден.
2. **Green — я:** класи через `make:class`, `make:controller` і `make:exception`, потім конфіг, прив'язка і роут. Після кожного `.php` hooks запускають Larastan (level 6) і тести. Помилки виправляю одразу, тести в фазі red падають очікувано. Наприкінці Pint на змінених файлах.
3. **Verify — `laravel-tester`:** цільові тести, потім увесь набір.

## Перевірка
- `docker compose exec app php artisan test --compact`: усі тести зелені.
- `docker compose exec app vendor/bin/phpstan --memory-limit=2G`: `[OK] No errors`.
- `curl -s http://localhost:8080/api/btc-price`: реальна ціна від `coingecko`.
- `docker compose exec app php artisan route:list --path=api`: маршрут зареєстровано.
- `jq -r 'select(.usage) | .usage' .agent-log/actions.jsonl | sort | uniq -c`: є `subagent:laravel-tester`, `hook:phpstan` і `hook:tests`.
