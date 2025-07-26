# Bot Panel

Сервісний шар для обробки Telegram-ботів, реалізований на основі патерна «Стратегія» та лінивої ініціалізації обробників через фабрики (замикання). Це забезпечує високу гнучкість і модульність архітектури.

## Основна ідея

### 1. Контролер і центральний обробник

Вхідні запити від Telegram через Webhook спочатку потрапляють у контролер, після чого передаються до центрального обробника — [`TelegramHandler`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/TelegramHandler.php). Тут запит проходить первинну обробку за допомогою middleware, реалізованих через Laravel Pipeline. Middleware виконують задачі логування, фільтрації та авторизації до того, як запит буде спрямований до конкретної стратегії.

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

## Переваги архітектури

* **Гнучкість**: легке додавання та зміна обробників для кожного бота.
* **Модульність**: чітке розділення відповідальностей і можливість перевикористання базової логіки.
* **Розширюваність**: швидке масштабування та додавання нових команд і типів повідомлень.

Завдяки такому підходу проєкт можна ефективно розвивати та підтримувати, не зачіпаючи базову структуру, а також створювати Telegram-ботів із різноманітним функціоналом у межах одного застосунку.

```

