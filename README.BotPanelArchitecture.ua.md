# Bot Panel

Сервісний шар для Telegram-ботів реалізований на патерні «Стратегія», з ледачим створенням обробників через фабрики (замикання) та виконанням усіх запитів у чергах Horizon. Така схема забезпечує передбачувану структуру, розширюваність і стабільну продуктивність.

## Основна ідея

### 1. Контролер і центральний обробник

Webhook обробляє [`BotController`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Http/Controllers/BotController.php), який формує payload і ставить job [`ProcessTelegramUpdate`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Jobs/ProcessTelegramUpdate.php) у чергу `telegram`, що обслуговується Horizon. Усередині job управління передається центральному обробнику — [`TelegramHandler`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/TelegramHandler.php).

На цьому етапі запит проходить первинну обробку через middleware, реалізовані за допомогою Laravel Pipeline. Вони виконують логування, фільтрацію та авторизацію перед передачею запиту конкретній стратегії.

### 2. Стратегії

Після проходження middleware `TelegramHandler` обирає стратегію, що відповідає типу поточного бота. Стратегія — це окремий конфігураційний клас, що наслідує базовий клас [`BaseService`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/BaseService.php).

Кожна стратегія успадковує стандартний набір обробників із `BaseService`, проте може легко перевизначити чи доповнити їх за потреби. Наприклад, стратегія для бота калорій: [`CaloriesService.php`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/CaloriesService.php).

### 3. Ієрархія обробників

На верхньому рівні ієрархії знаходиться метод `getUpdateHandlers()` класу [`BaseService`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/BaseService.php), чия задача — визначити фабрики обробників для кожного типу оновлення (наприклад, `message`, `my_chat_member`, `callback_query`):

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
````

Кожна фабрика створює обробники наступного рівня, ініціалізуючи їх лише тоді, коли вони дійсно потрібні.

Наприклад, для типу повідомлення `message` фабрика створює обробник `MessageUpdateHandler`, передаючи йому фабрики для обробки конкретних типів повідомлень:

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

Таким чином, ієрархія обробки запитів виглядає так:

* **UpdateHandlers** (наприклад, `MessageUpdateHandler`, `CallbackQueryHandler`)
* **MessageHandlers** (наприклад, `TextMessageHandler`, `AudioMessageHandler`)
* Конкретні команди та типи повідомлень (наприклад, `/start`, `/default`, голосові повідомлення тощо)

### 4. Перевизначення та доповнення обробників

Стратегії дозволяють гнучко налаштовувати обробники на кожному рівні. Наприклад, стратегія [`CaloriesService`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/CaloriesService.php) може перевизначити метод `getMessageHandlers()` і вказати власний обробник для голосових повідомлень:

```php
protected function getMessageHandlers(): array
{
    $handlers = parent::getMessageHandlers();

    $handlers['voice'] = fn () => app(AudioMessageHandler::class);

    return $handlers;
}
```

Також стратегії можуть використовувати додаткові методи та утиліти, такі як `Utilities::applySynonyms()`, для налаштування команд і підвищення зручності взаємодії з користувачем.

### 5. Стійкість обробки

Laravel-job [`ProcessTelegramUpdate`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Jobs/ProcessTelegramUpdate.php) гарантує, що кожен апдейт Telegram обробляється лише один раз і не запускається повторно у паралельних воркерах. Завдання виконуються у виділеній черзі з rate-limit, коректно відкладаються у разі `retry_after`, а помилки логуються та передаються далі для моніторингу.

## Переваги архітектури

* **Гнучкість**: легке додавання та зміна обробників для кожного бота.
* **Модульність**: чітке розділення відповідальностей і можливість перевикористання базової логіки.
* **Розширюваність**: швидке масштабування та додавання нових команд і типів повідомлень.

Завдяки такому підходу проєкт можна ефективно розвивати та підтримувати, не зачіпаючи базову структуру, а також створювати Telegram-ботів із різноманітним функціоналом у межах одного застосунку.


