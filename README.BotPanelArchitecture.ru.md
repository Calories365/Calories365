# Bot Panel

Сервисный слой для Telegram-ботов реализован на паттерне «Стратегия», с ленивым созданием обработчиков через фабрики (замыкания) и выполнением всех запросов в очередях Horizon. Такая схема обеспечивает предсказуемую структуру, расширяемость и стабильную производительность.

## Основная идея

### 1. Контроллер и центральный обработчик

Webhook принимает [`BotController`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Http/Controllers/BotController.php), контроллер формирует payload и ставит job [`ProcessTelegramUpdate`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Jobs/ProcessTelegramUpdate.php) в очередь `telegram`, которую обслуживает Horizon. Внутри job управление передаётся центральному обработчику — [`TelegramHandler`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/TelegramHandler.php).

Здесь запрос проходит первичную обработку при помощи middleware, реализованных через Laravel Pipeline. Middleware выполняют задачи логирования, фильтрации и авторизации до того, как запрос будет направлен к конкретной стратегии.

### 2. Стратегии

После прохождения middleware `TelegramHandler` выбирает стратегию, соответствующую типу текущего бота. Стратегия представляет собой отдельный конфигурационный класс, наследующий общий базовый класс [`BaseService`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/BaseService.php).

Каждая стратегия наследует стандартный набор обработчиков из `BaseService`, но может легко переопределить или дополнить их по необходимости. Например, стратегия для бота калорий: [`CaloriesService.php`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/CaloriesService.php).

### 3. Иерархия обработчиков

На верхнем уровне иерархии находится метод `getUpdateHandlers()` класса [`BaseService`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/BaseService.php), задача которого — определить фабрики обработчиков для каждого типа обновления (например, `message`, `my_chat_member`, `callback_query`):

```php
protected function getUpdateHandlers(): array
{
    return [
        'message' => function () {
            return app(MessageUpdateHandler::class, [
                'messageHandlers' => $this->getMessageHandlers(),
            ]);
        },
        'my_chat_member' => function () {
            return app(MyChatMemberUpdateHandler::class);
        },
        'callback_query' => function () {
            return app(CallbackQueryHandler::class, [
                'callbackQueryHandlers' => $this->getCallbackQueryHandlers(),
            ]);
        },
    ];
}
```

Каждая фабрика создаёт обработчики следующего уровня, инициализируя их только тогда, когда они действительно нужны.

Например, для типа сообщения `message` фабрика создаёт обработчик `MessageUpdateHandler`, передавая ему фабрики для обработки конкретных типов сообщений:

```php
protected function getMessageHandlers(): array
{
    return [
        'text' => function () {
            return app(TextMessageHandler::class, [
                'textMessageHandlers' => $this->getTextMessageHandlers(),
            ]);
        },
        'voice' => function () {
            return app(AudioMessageHandler::class);
        },
    ];
}
```

Таким образом, иерархия обработки запросов выглядит так:

* **UpdateHandlers** (например, `MessageUpdateHandler`, `CallbackQueryHandler`)
* **MessageHandlers** (например, `TextMessageHandler`, `AudioMessageHandler`)
* Конкретные команды и типы сообщений (например, `/start`, `/default`, голосовые сообщения и др.)

### 4. Переопределение и дополнение обработчиков

Стратегии позволяют гибко настраивать обработчики на каждом уровне. Например, стратегия [`CaloriesService`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/CaloriesService.php) может переопределить метод `getMessageHandlers()` и указать собственный обработчик для голосовых сообщений:

```php
protected function getMessageHandlers(): array
{
    $handlers = parent::getMessageHandlers();

    $handlers['voice'] = fn () => app(AudioMessageHandler::class);

    return $handlers;
}
```

Также стратегии могут использовать дополнительные методы и утилиты, такие как `Utilities::applySynonyms()`, для настройки команд и повышения удобства взаимодействия с пользователем.

### 5. Устойчивость обработки

Laravel-job [`ProcessTelegramUpdate`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Jobs/ProcessTelegramUpdate.php) гарантирует, что каждый апдейт Telegram обрабатывается один раз и не запускается повторно в параллельных воркерах. Задачи выполняются в выделенной очереди с rate-limit, корректно откладываются при retry_after, а ошибки логируются и пробрасываются дальше для мониторинга.

## Преимущества архитектуры

* **Гибкость**: лёгкое добавление и изменение обработчиков для каждого бота.
* **Модульность**: чёткое разделение ответственности и возможность переиспользования базовой логики.
* **Расширяемость**: быстрое масштабирование и добавление новых команд и типов сообщений.

Благодаря такому подходу проект можно эффективно развивать и поддерживать, не затрагивая базовую структуру, а также создавать Telegram-ботов с разнообразным функционалом в рамках одного приложения.
