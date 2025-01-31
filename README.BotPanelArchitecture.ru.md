# Bot Panel

Описание сервисного слоя для телеграм ботов.

## Основная идея

1. **Слой сервисов (Service Layer)**
   Вся логика Telegram-ботов расположена в сервисном слое. Запросы от Telegram (через Webhook) обрабатываются в контроллере и перенаправляются в специальный **TelegramHandler**.

2. **[TelegramHandler](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/TelegramHandler.php)**
   - Является центральной точкой принятия решения, какой бот (стратегию) вызывать.  
   - При необходимости запускает назначенные **middleware** (Laravel Pipeline) для конкретной стратегии.  
   - Передаёт управление выбранной стратегии.

3. **Стратегии**
   - Каждая стратегия — это «конфигурационный файл» для отдельного бота.  
   - Пример стратегии для бота калорий: [`CaloriesService.php`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/CaloriesService.php).  
   - Внутри стратегии указываются нужные обработчики для различных типов апдейтов (сообщения, колбэки и т.д.).

4. **[BaseService](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/BaseService.php)**
   - Все стратегии наследуют этот базовый класс.  
   - В `BaseService` задаётся общая логика инициализации **UpdateHandlers**, а также распределения входящих обновлений (message, callback_query, my_chat_member и т.п.).

### Пример инициализации Update Handlers

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

В каждой стратегии (например, `CaloriesService`) можно переопределять или дополнять базовые хендлеры из `BaseService`.

## Pipeline обработки

1. **Контроллер** → **TelegramHandler**
2. **TelegramHandler** (проверяет тип бота, вызывает нужную стратегию, запускает middleware)
3. **Стратегия** (наследует `BaseService`, где поэтапно инициализируются хендлеры)
4. **Update Handlers** (например, `MessageUpdateHandler`, `CallbackQueryHandler`)
5. **Message Handlers** (например, текстовые сообщения `/start`, `/stats`, голосовые, фото и т.д.)

## Пример расширения Message Handlers

В базовом классе (`BaseService`) есть метод:

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

В **стратегии** мы можем его дополнить:

```php
protected function getMessageHandlers(): array
{
    $messageHandlers = parent::getMessageHandlers();

    // Переопределяем или добавляем нужный класс
    $messageHandlers['voice'] = app(AudioMessageHandler::class);

    return $messageHandlers;
}
```

Таким образом, при получении голосового сообщения (`voice`) будет использоваться уже переопределённый обработчик. Аналогичным образом можно регистрировать или менять хендлеры для других типов апдейтов.

## Пример сценария обработки
- Пользователь отправляет боту команду `/start`.
- **TelegramHandler** определяет, к какому боту (стратегии) привязан текущий `botName`, и применяет требуемые middleware.
- Затем вызывается `handle()` стратегии (например, `CaloriesService`).
- Внутри `BaseService` определяется тип обновления: `message`.
- `MessageUpdateHandler` ищет, какой именно Message Handler подходит для `text` — это `TextMessageHandler`.
- `TextMessageHandler` проверяет, что команда `/start` есть в списке, и вызывает соответствующий обработчик (например, `StartMessageHandler`).
- В итоге пользователь получает ответ, определённый в логике `StartMessageHandler`.

## Вывод
- **Гибкость**: каждый бот конфигурируется в отдельном классе-стратегии и может переопределять/дополнять нужные обработчики.
- **Модульность**: единый `TelegramHandler` и мидлвары по необходимости подключаются к разным стратегиям.
- **Расширяемость**: легко добавлять новые команды, новые типы сообщений и колбэков.

Благодаря такой архитектуре проект можно масштабировать, не затрагивая базовую структуру, и создавать Telegram-ботов с разным функционалом в рамках одного приложения.
