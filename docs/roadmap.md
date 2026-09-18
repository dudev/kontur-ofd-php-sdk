# Roadmap — верхнеуровневые задачи

Статус: планирование, кода ещё нет. Цель — не тонкая обёртка над HTTP (голые массивы туда-обратно),
а настоящий SDK: типизированные объекты на входе/выходе, разделение транспорта и домена, свои
исключения по кодам ошибок API, отдельная пагинация — тем же принципом, что уже сложился в
`packages/yclients-php-sdk` (`Transport.php`/`Http/RawResponse.php`, `<Entity>/<Entity>Api.php`,
`Exception/*`, `Pagination/Page.php`).

Источник истины по самому API — официальные доки, зеркало в `packages/reference/ofd-api-docs`
(`.rst`, удобнее для грепа, чем HTML на `docs-ofd-api.kontur.ru`); что из этого relsy-backend
реально нужно для чеков — `relsy/backend/docs/ofd-receipts-design.md` (см. `relsy-ofd-receipts`
worktree). **Этот SDK не знает про relsy** — ни про `Branch`/`Cashbox`-маппинг, ни про хранение
чеков в БД, ни про `OfdAuthSettings`. Его дело — чистый типизированный клиент API, который потом
использует `relsy-backend` (и потенциально что угодно ещё).

## M1. Транспорт и общая инфраструктура ошибок

- `Transport` — тонкий слой поверх `psr/http-client`+`psr/http-factory` (не конкретной библиотеки
  вроде `symfony/http-client`/Guzzle — SDK не должен навязывать потребителю свой HTTP-стек и
  тащить в него второй конкретный клиент рядом с уже стоящим; конкретную реализацию берём через
  `php-http/discovery`, либо потребитель инжектирует свою в конструктор клиента). Два базовых URL
  (auth — всегда `https://api.kontur.ru`, data — `https://ofd-api.kontur.ru` прод /
  `https://ofd-project.kontur.ru:11002` тест, оба конфигурируемые, не хардкод), заголовки
  `Authorization: auth.sid <sid>` + `X-Kontur-Ofd-ApiKey: <key>` на каждый запрос к data-хосту
  (auth-хост их не требует).
- Разбор тела ошибки (`{errorCodeId, errorCode, moreInfo, userMessage}`) в исключения:
  - `KonturOfdException` — базовое.
  - `AuthenticationException` (`401002`/`401003` — не хватает/протухла `auth.sid`, не хватает/неверен
    ключ интегратора) — вызывающий код (relsy) решает, переаутентифицироваться самому или сообщить,
    что нужна ручная реаутентификация (SDK этого не знает, см. выше).
  - `AccessDeniedException` (`403000`/`403002`/`403003` — нет доступа к организации/кассе/периоду).
  - `NotFoundException` (`404000`/`404001`).
  - `ValidationException` (`400xxx` — некорректные параметры запроса, включая
    `400000`/`400002`/`400007`/`400008`/`400009` из `Responses.rst`).
  - Не мапленный код — общий `KonturOfdException` с сырым `errorCodeId` внутри, не падать с
    generic HTTP-исключением без контекста.

## M2. Аутентификация — примитивы, не решение "как это сделано у relsy"

SDK не реализует GOST-крипто и не решает, откуда берётся `auth.sid` — три способа из
`ofd-receipts-design.md` §3 (пароль на backend / пароль на фронте / ЭП на фронте) — это
архитектура вызывающего кода. SDK даёт три независимых примитива, вызывающий использует нужный:

- `authenticateByPass(login, password): Sid` — `POST /auth/authenticate-by-pass`.
- `withSid(string $sid): self` (или конструктор клиента прямо с готовым `Sid`) — для случая, когда
  `Sid` уже получен снаружи (не важно, паролем или сертификатом, не важно кем).
- `authenticateByCert(base64Certificate): EncryptedKeyResult` +
  `approveCert(thumbprint, decryptedBytes): Sid` — только сетевые вызовы, само шифрование/
  расшифровка (ГОСТ 28147-89) — забота вызывающего (см. открытый вопрос 2 ниже).

## M3. Организации и кассы

- `OrganizationsApi::list(): Organization[]`, `::get(id): Organization`.
- `CashboxesApi::list(organizationId): Cashbox[]`, `::get(organizationId, kktRegId): Cashbox`.
- Типизированные `Organization`/`Cashbox`/`FiscalDrive` value-объекты (не ассоц-массивы) —
  `Cashbox` включает `addresses[]`/`fiscalDrives[]`/`permissionFrom`/`permissionTo` как есть в API.

## M4. Документы — основная ценность SDK

- `DocumentsApi::byPeriod(organizationId, kktRegId, dateFrom, dateTo, types?, offset?, limit?)` —
  одна страница, типизированный `DocumentsPage` (`documents`, `nextOffset`).
- Итератор/генератор поверх `byPeriod()`, который сам пагинирует до конца — условие остановки:
  `nextOffset` ответа совпал с `offset` запроса (см. `ofd-receipts-design.md` §6 — надёжнее, чем
  просто пустой `documents: []`, подтверждено независимой Python-реализацией в
  `packages/reference/kontur-ofd-python-gist`). Вызывающий код сам решает, что делать с "документов
  пока нет, но курсор не продвинулся" (relsy, например, добавляет нахлёст окна дат — это тоже не
  забота SDK).
- `DocumentsApi::get(organizationId, kktRegId, documentId): Document`.
- Типизированная модель на каждый тип документа из `Structures/` (`ReceiptOrBso`, `OpenShift`,
  `CloseShift`, `CurrentStateReport`, `FiscalReport`, `FiscalReportCorrection`, `CloseArchive`) —
  общий `Document` как discriminated union/интерфейс с `getType(): DocumentType`, не один
  raw-массив на всё. `ReceiptOrBso` — приоритет (`receiptCode`/`bsoCode`, `items[]`, ~15
  `nds*`/`ndsCalculated*` полей, `fiscalSign` — **int64/string, не int**, превышает 2^31 в реальных
  данных); остальные типы — заготовки по мере надобности, не блокируют первый релиз.
- **Не реализовывать v1 (`tickets/*`)** — устаревшие, по документации "временно поддерживаются",
  `v2` их полностью покрывает и умеет то, чего `v1` не умеет (постраничная выдача,
  `receiveDateTimeUtc`).

## M5. Статистика (низкий приоритет)

`cashboxes/statistics/by-days`, `.../by-shifts`, `organizations/statistics/by-days` — агрегаты,
не разбирались подробно ни в этом SDK, ни в `ofd-receipts-design.md`. Делать в последнюю очередь,
только если реально понадобится (relsy сейчас про сами документы, не про готовые отчёты).

## M6. Тесты

- Юнит-тесты на каждый `*Api` класс — мокать `Transport`/HTTP-слой (PSR-18-стиль, как
  `yclients-php-sdk`), не ходить в сеть.
- Опционально — интеграционный набор против тестовой площадки Контур.ОФД
  (`https://ofd-project.kontur.ru:11002/`, есть демо-организации с автогенерируемыми документами,
  доступ бесплатный) под реальными кредами из переменных окружения, не запускается в обычном CI
  (нет кредов) — отдельный workflow/job с `if: env credentials present`, по аналогии с тем, как
  `docs/GetAccess.rst` описывает получение тестового доступа.

## Открытые вопросы

1. **Формат типизированных моделей** — `readonly class` с публичными свойствами (как
   `PaymentItemResponseDto` в relsy-backend) или геттеры? Голосую за `readonly class` + именованные
   конструкторы `fromArray()`/`fromJson()` — проще тестировать, не тянет PSR-специфичные интерфейсы.
2. **Кто отвечает за ГОСТ-крипто в M2** — SDK принципиально не берёт на себя ГОСТ 28147-89
   (расшифровка `EncryptedKey`/подготовка байтов для `approve-cert`), это либо вызывающий код (уже
   решено для relsy — браузерная церемония, см. `ofd-receipts-design.md` §3), либо отдельный
   опциональный адаптер (`require-dev`/`suggest`) для тех, кто всё же захочет делать это на PHP
   (CryptoPro CSP CLI/`gost-engine`) — заводить только если реально понадобится, не заранее.
3. **Пагинация — итератор или готовый массив** — `byPeriodAll()`, жадно собирающий всё в память (как
   `kontur-ofd-python-gist/kontur.py`), или `\Generator`, отдающий страницы/документы лениво?
   Голосую за `\Generator` — период синхронизации может быть большим, копить всё в памяти
   не обязательно.
