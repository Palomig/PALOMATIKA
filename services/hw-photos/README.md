# hw-photos

Хранилище фото решений домашки PALOMATIKA. Живёт на dev-VPS (78.17.28.40), публикуется
Apache'ем на `https://palomig.ru/hw-photos/`.

**Зачем отдельный сервис.** Прод Laravel — шаред-хостинг Timeweb: там `public/storage`
не симлинк, а обычная папка-копия, поэтому по `/storage/...` всегда 404; канал и место
дорогие, а фото тетради с телефона — это 3–20 МБ. Ученик грузит снимок напрямую сюда,
Timeweb в передаче файла не участвует вовсе.

## Как устроено доверие

Один общий секрет с Laravel (`HW_PHOTOS_SECRET`), три подписи, ни одного сетевого
вызова между Laravel и сервисом:

| Что | Подписывает | Проверяет | Смысл |
|---|---|---|---|
| upload-токен `t.<payload>.<sig>` | Laravel | сервис | кому можно грузить (assignment/task/student + exp) |
| `photo_id` `p.<payload>.<sig>` | сервис | Laravel | чьё это фото — чужой id в свою домашку не подставить |
| read-ссылка `?exp=&sig=` | Laravel | сервис | кому можно смотреть, ссылка короткоживущая |

Публичных ссылок на тетради учеников не существует: без свежей подписи — 403.

## Эндпоинты

| Метод | Путь | Что |
|---|---|---|
| GET | `/healthz` | живость (использует фолбэк-логика и мониторинг) |
| POST | `/v1/photos` | `Authorization: Bearer <upload-токен>`, multipart-поле `photo` → `{photo_id, bytes, width, height, stored_as}` |
| GET | `/v1/photo/<photo_id>?exp=&sig=[&w=400\|800\|1600]` | отдать фото или миниатюру |

Одно решение — до 10 страниц: ученик грузит каждую отдельным запросом и получает свой `photo_id`.

Что делает при загрузке: проверяет магические байты (переименованный PDF не пройдёт),
пережимает в JPEG с поворотом по EXIF и ужимает до 2000px по большой стороне,
режет вес (11 МБ → ~1.8 МБ). Если декодировать не удалось — кладёт оригинал:
потерять фото решения хуже, чем хранить тяжёлый файл. Лимит 25 МБ,
не больше 30 загрузок в час на связку assignment+task+student.

## Эксплуатация

```bash
systemctl status hw-photos          # юнит: /etc/systemd/system/hw-photos.service
journalctl -u hw-photos -n 50       # логи
node test/smoke.mjs                 # 15 проверок локально
node test/smoke.mjs https://palomig.ru/hw-photos   # то же через Apache
```

- **Конфиг:** `/home/dev/.agent-secrets/hw-photos.env` (`HW_PHOTOS_SECRET`, `HW_PHOTOS_PORT=4320`,
  `HW_PHOTOS_DATA`). Тот же секрет — в `.env` прода Laravel (`HW_PHOTOS_SECRET`, `HW_PHOTOS_URL`).
- **Данные:** `/home/dev/hw-photos-data/<assignment_id>/`, вне DocumentRoot.
- **Бэкап:** `backup.sh` по крону в 04:17, 7 суточных архивов в `/home/dev/backups/hw-photos/`.
- **Сторож:** `healthcheck.sh` по крону каждые 5 минут — дёргает `/healthz`, при отказе сам перезапускает юнит и пишет в телегу (@my_claude_stas_bot). Сообщение уходит только при СМЕНЕ состояния, иначе за ночь набежит полсотни одинаковых. Лог — `healthcheck.log`, состояние — `.health-state`.
- **Apache:** `ProxyPass /hw-photos/` в `/etc/apache2/sites-available/palomig-le-ssl.conf`.

## Синхронизация с репозиторием

Источник истины — `services/hw-photos/` в репозитории PALOMATIKA. Рантайм живёт отдельно
(`/home/dev/hw-photos`), чтобы переключение ветки в чекауте не роняло сервис. После
правок в репозитории:

```bash
services/hw-photos/deploy.sh          # копирует файлы в рантайм и перезапускает юнит
```

## Если сервис лёг

Ничего не встаёт: Laravel не получает тикет (или загрузка падает) и ученик сдаёт фото
прежним путём — файл сохраняется на хостинге в `storage/app/public/homework_solutions/`.
Такие сабмишны видно по заполненному `solution_photo_path` вместо `solution_photo_remote_id`.
