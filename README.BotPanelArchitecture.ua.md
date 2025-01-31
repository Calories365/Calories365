# Bot Panel

Опис сервісного шару для Telegram-ботів.

## Основна ідея

1. **Шар сервісів (Service Layer)**
   Уся логіка Telegram-ботів розташована в сервісному шарі. Запити від Telegram (через Webhook) обробляються в контролері та перенаправляються до спеціального **TelegramHandler**.

2. **[TelegramHandler](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/TelegramHandler.php)**
    - Є центральною точкою прийняття рішення, який бот (стратегію) викликати.
    - При необхідності запускає призначені **middleware** (Laravel Pipeline) для конкретної стратегії.
    - Передає управління вибраній стратегії.

3. **Стратегії**
    - Кожна стратегія — це «конфігураційний файл» для окремого бота.
    - Приклад стратегії для бота калорій: [`CaloriesService.php`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/CaloriesService.php).
    - В середині стратегії вказуються потрібні обробники для різних типів оновлень (повідомлення, колбеки тощо).

4. **[BaseService](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/BaseService.php)**
    - Всі стратегії наслідують цей базовий клас.
    - У `BaseService` задається загальна логіка ініціалізації **UpdateHandlers**, а також розподілу вхідних оновлень (message, callback_query, my_chat_member тощо).

### Приклад ініціалізації Update Handlers

```php
protected function getUpdateHandlers(): array
{
    $messageUpdateHandler    = new MessageUpdateHandler($this->getMessageHandlers());
    $myChatMemberUpdateHandler = new MyChatMemberUpdateHandler();
    $callbackQueryHandler    = new CallbackQueryHandler($this->getCallbackQueryHandlers());

    return [
        'message'        => $messageUpdateHandler,
        'my_chat_member' => $myChatMemberUpdateHandler,
        'callback_query' => $callbackQueryHandler
    ];
}
```

У кожній стратегії (наприклад, `CaloriesService`) можна перевизначати або доповнювати базові хендлери з `BaseService`.

## Pipeline обробки

1. **Контролер** → **TelegramHandler**
2. **TelegramHandler** (перевіряє тип бота, викликає потрібну стратегію, запускає middleware)
3. **Стратегія** (наслідує `BaseService`, де поетапно ініціалізуються хендлери)
4. **Update Handlers** (наприклад, `MessageUpdateHandler`, `CallbackQueryHandler`)
5. **Message Handlers** (наприклад, текстові повідомлення `/start`, `/stats`, голосові, фото тощо)

## Приклад розширення Message Handlers

У базовому класі (`BaseService`) є метод:

```php
protected function getMessageHandlers(): array
{
    $textMessageHandler  = new TextMessageHandler($this->getTextMessageHandlers());
    $audioMessageHandler = new AudioMessageHandler();

    return [
        'text'  => $textMessageHandler,
        'voice' => $audioMessageHandler,
    ];
}
```

У **стратегії** ми можемо його доповнити:

```php
protected function getMessageHandlers(): array
{
    $messageHandlers = parent::getMessageHandlers();

    // Перевизначаємо або додаємо потрібний клас
    $messageHandlers['voice'] = app(AudioMessageHandler::class);

    return $messageHandlers;
}
```

Таким чином, при отриманні голосового повідомлення (`voice`) буде використовуватись вже перевизначений обробник. Аналогічним чином можна реєструвати або змінювати хендлери для інших типів оновлень.

## Приклад сценарію обробки
- Користувач відправляє боту команду `/start`.
- **TelegramHandler** визначає, до якого бота (стратегії) прив'язаний поточний `botName`, і застосовує потрібні middleware.
- Потім викликається `handle()` стратегії (наприклад, `CaloriesService`).
- В середині `BaseService` визначається тип оновлення: `message`.
- `MessageUpdateHandler` шукає, який саме Message Handler підходить для `text` — це `TextMessageHandler`.
- `TextMessageHandler` перевіряє, що команда `/start` є в списку, і викликає відповідний обробник (наприклад, `StartMessageHandler`).
- В результаті користувач отримує відповідь, визначену в логіці `StartMessageHandler`.

## Висновок
- **Гнучкість**: кожен бот конфігурується в окремому класі-стратегії і може перевизначати/доповнювати потрібні обробники.
- **Модульність**: єдиний `TelegramHandler` і middleware за необхідності підключаються до різних стратегій.
- **Розширюваність**: легко додавати нові команди, нові типи повідомлень і колбеків.

Завдяки такій архітектурі проект можна масштабувати, не торкаючись базової структури, і створювати Telegram-ботів з різним функціоналом в рамках одного додатку.
