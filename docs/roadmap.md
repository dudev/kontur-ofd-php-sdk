# Roadmap — верхнеуровневые задачи

Статус: M1–M4 реализованы (2026-09-18), M5 и M6 — 2026-09-25 (интеграционный набор написан, но
ещё не прогонялся с настоящими кредами — см. открытый вопрос 4). Цель — не тонкая обёртка над HTTP (голые массивы туда-обратно),
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

## ~~M1~~. Транспорт и общая инфраструктура ошибок — реализовано

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

## ~~M2~~. Аутентификация — примитивы, не решение "как это сделано у relsy" — реализовано

SDK не реализует GOST-крипто и не решает, откуда берётся `auth.sid` — три способа из
`ofd-receipts-design.md` §3 (пароль на backend / пароль на фронте / ЭП на фронте) — это
архитектура вызывающего кода. SDK даёт три независимых примитива, вызывающий использует нужный:

- `AuthClient::authenticateByPass(login, password): AuthResult` — `POST /auth/authenticate-by-pass`.
- `KonturOfdClient::withSid(string $sid)`/`getSid()` — для случая, когда `Sid` уже получен снаружи
  (не важно, паролем или сертификатом, не важно кем).
- `AuthClient::authenticateByCert(base64Certificate): EncryptedKeyResult` +
  `approveCert(approveCertUrl, decryptedBytes): AuthResult` — только сетевые вызовы, само
  шифрование/расшифровка (ГОСТ 28147-89) — забота вызывающего (см. открытый вопрос 2 ниже).
  `approveCert()` принимает готовый `approveCertUrl` из `EncryptedKeyResult` (уже абсолютный URL с
  `thumbprint` в query, отданный сервером), не собирает путь сам — версия API в этом пути у
  Контур.ОФД плавает (`v5.9` в примере документации), идти по ссылке надёжнее, чем угадывать.

## ~~M3~~. Организации и кассы — реализовано

- `OrganizationsApi::list(): Organization[]`, `::get(id): Organization`.
- `CashboxesApi::list(organizationId): Cashbox[]`, `::get(organizationId, kktRegId): Cashbox`.
- Типизированные `Organization`/`Cashbox`/`FiscalDrive` value-объекты (не ассоц-массивы) —
  `Cashbox` включает `addresses[]`/`fiscalDrives[]`/`permissionFrom`/`permissionTo` как есть в API.

## ~~M4~~. Документы — основная ценность SDK — реализовано

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
  общий `Document`-интерфейс с `getType(): DocumentType`, не один raw-массив на всё. `Receipt` —
  приоритет (`receiptCode`/`bsoCode` → `DocumentType::Receipt`/`Bso`/`...Correction`, `items[]`,
  подмножество `nds*`/`ndsCalculated*` полей — только непустые, в `ndsBreakdown`), `fiscalSign` —
  обычный PHP `int` (не строка — на 64-битном PHP, единственная поддерживаемая архитектура для
  `php: ^8.2` в этом composer.json, диапазона хватает даже с запасом на значения из реальных
  данных, превышающие 2^31); остальные типы документа — `GenericDocument` (сырые поля через
  `getRawData()`), заготовка по мере надобности, не блокирует первый релиз.
- **Не реализовывать v1 (`tickets/*`)** — устаревшие, по документации "временно поддерживаются",
  `v2` их полностью покрывает и умеет то, чего `v1` не умеет (постраничная выдача,
  `receiveDateTimeUtc`).

## ~~M5~~. Статистика — реализовано

Готовые агрегаты по чекам (БСО), `GET .../statistics/cash-receipt/*` (`http/cashboxes-statistics-*`,
`http/organizations-statistics-by-days` на `docs-ofd-api.kontur.ru`). relsy они пока не нужны (там — сами документы), но
методов всего три и форма ответа простая, поэтому сделаны, чтобы SDK покрывал API v2 целиком.

- `StatisticsApi::cashboxByDays(organizationId, kktRegId, dateFrom, dateTo): DayStatistics[]`,
  `::cashboxByShifts(...): ShiftStatistics[]`, `::organizationByDays(organizationId, dateFrom,
  dateTo): DayStatistics[]`. Период — только даты (`from`/`to` в формате `Y-m-d`, обе границы
  включительно), время у `DateTimeImmutable` отбрасывается.
- `DayStatistics`/`ShiftStatistics` — по `OperationTotals` на каждый признак расчёта
  (`sell`/`returnSell`/`buy`/`returnBuy`): суммы в копейках, количество чеков, блок `nds` как
  `ndsBreakdown` (ключи API как есть — `rate20`, `calculatedWithRate20`, ...). Набор ставок не
  зашит: сейчас документированы 5/7/10/18/20/22 (в зеркале `skbkontur/ofd-api-docs` от 2022 года
  — только 10/18/20), следующая новая ставка придёт без правок SDK.
- `organizationByDays()` доступен только интегратору с доступом ко всем кассам организации (в том
  числе будущим) — иначе ошибка доступа, `AccessDeniedException` (M1).
- Особенности API, которые SDK не сглаживает: дня без смен и чеков в ответе нет вовсе (день со
  сменой, но без чеков — есть, с нулями); у смены, не целиком попавшей в период, суммы только за
  попавшую часть, а `shiftOpen`/`shiftClose` — `null`, если отчёт об открытии/закрытии вне периода.

## ~~M6~~. Тесты — реализовано

- ~~Юнит-тесты на каждый `*Api` класс~~ — сделано: `Http\Mock\Client` (PSR-18) +
  `Nyholm\Psr7\Factory\Psr17Factory` (PSR-17), без сети, тесты на M1–M5 целиком, включая оба
  условия остановки `byPeriodAll()` (см. `tests/Document/DocumentsApiTest.php`).
- ~~Интеграционный набор против тестовой площадки~~ — сделано: `tests/Integration/`, отдельный
  testsuite `integration` (обычный `vendor/bin/phpunit` его не запускает, `defaultTestSuite="unit"`).
  По умолчанию — тестовая площадка `https://ofd-project.kontur.ru:11002/` (демо-организации с
  автогенерируемыми документами, доступ бесплатный). Креды — из окружения: `KONTUR_OFD_API_KEY` +
  `KONTUR_OFD_LOGIN`/`KONTUR_OFD_PASSWORD` (или готовый `KONTUR_OFD_SID`), хост —
  `KONTUR_OFD_DATA_BASE_URI`; без кредов тесты пропускаются, а не падают. Реальный PSR-18-клиент —
  Guzzle в `require-dev` (его же использует relsy-backend), находится через `php-http/discovery`.
  - Покрыто: аутентификация по паролю и отказ по неизвестному `auth.sid` (401); организации и
    кассы (список → `get()` по id); документы (`limit`, типизированный `Receipt`, пагинация
    `byPeriodAll()` дальше первой страницы, `get()` по id, 400 на перевёрнутый период);
    статистика (все три метода; `organizationByDays()` без полного доступа к организации —
    пропуск, а не падение). Только чтение, ничего не создаётся.
  - Проверки не завязаны на конкретные демо-данные (id организаций, суммы) — только на связность
    ответов между собой и документированное поведение; если данных нет (нет касс, нет чеков в
    окне), тест пропускается с объяснением.
  - CI: отдельный workflow `.github/workflows/integration.yml` — вручную (`workflow_dispatch`) и
    раз в неделю; секреты `KONTUR_OFD_API_KEY`/`KONTUR_OFD_LOGIN`/`KONTUR_OFD_PASSWORD`, без них
    job ничего не делает (notice в логе). В обычный CI на push/PR не включён — у форков секретов
    нет, и дёргать внешнюю площадку на каждый коммит незачем.

## Открытые вопросы

1. ~~Формат типизированных моделей~~ — **решено**: `readonly class` с публичными свойствами +
   именованный конструктор `fromArray()` (без интерфейса сериализации — не нужен, `fromArray()`
   вызывается явно из `*Api`-классов).
2. **Кто отвечает за ГОСТ-крипто в M2** — SDK принципиально не берёт на себя ГОСТ 28147-89
   (расшифровка `EncryptedKey`/подготовка байтов для `approve-cert`), это либо вызывающий код (уже
   решено для relsy — браузерная церемония, см. `ofd-receipts-design.md` §3), либо отдельный
   опциональный адаптер (`require-dev`/`suggest`) для тех, кто всё же захочет делать это на PHP
   (CryptoPro CSP CLI/`gost-engine`) — заводить только если реально понадобится, не заранее.
   Всё ещё открыт — адаптер не заводился.
3. ~~Пагинация — итератор или готовый массив~~ — **решено**: `\Generator` (`byPeriodAll()`), не
   жадный массив — период синхронизации может быть большим, копить всё в памяти не обязательно.
4. **Интеграционные тесты против тестовой площадки** (M6) — написаны, но с настоящими кредами ещё
   не прогонялись: нужен ключ интегратора и логин на тестовую площадку (`GetAccess.rst`),
   получить их — организационный шаг. Без кредов проверено только то, что можно: набор целиком
   пропускается, а отказ по неизвестному `auth.sid` воспроизведён на реальном HTTP (Guzzle через
   discovery). Когда креды появятся — завести секреты и запустить workflow `Integration` вручную.
