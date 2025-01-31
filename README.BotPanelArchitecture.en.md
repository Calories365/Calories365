# Bot Panel

Description of the service layer for Telegram bots.

## Main Idea

1. **Service Layer**
   All the logic of Telegram bots is located in the service layer. Requests from Telegram (via Webhook) are processed in the controller and forwarded to a special **TelegramHandler**.

2. **[TelegramHandler](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/TelegramHandler.php)**
    - Serves as the central point for deciding which bot (strategy) to invoke.
    - If necessary, it launches the designated **middleware** (Laravel Pipeline) for the specific strategy.
    - Transfers control to the selected strategy.

3. **Strategies**
    - Each strategy is a "configuration file" for an individual bot.
    - Example of a strategy for the calorie bot: [`CaloriesService.php`](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/CaloriesService.php).
    - Within the strategy, the necessary handlers for various types of updates (messages, callbacks, etc.) are specified.

4. **[BaseService](https://github.com/Maaaaxim/bot-panel/blob/main/app/Services/TelegramServices/BaseService.php)**
    - All strategies inherit from this base class.
    - In `BaseService`, the general logic for initializing **UpdateHandlers** is defined, as well as the distribution of incoming updates (message, callback_query, my_chat_member, etc.).

### Example of Initializing Update Handlers

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

In each strategy (e.g., `CaloriesService`), you can override or extend the base handlers from `BaseService`.

## Processing Pipeline

1. **Controller** → **TelegramHandler**
2. **TelegramHandler** (checks the bot type, invokes the necessary strategy, launches middleware)
3. **Strategy** (inherits from `BaseService`, where handlers are initialized step by step)
4. **Update Handlers** (e.g., `MessageUpdateHandler`, `CallbackQueryHandler`)
5. **Message Handlers** (e.g., text messages `/start`, `/stats`, voice messages, photos, etc.)

## Example of Extending Message Handlers

In the base class (`BaseService`), there is a method:

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

In the **strategy**, we can extend it:

```php
protected function getMessageHandlers(): array
{
    $messageHandlers = parent::getMessageHandlers();

    // Override or add the necessary class
    $messageHandlers['voice'] = app(AudioMessageHandler::class);

    return $messageHandlers;
}
```

Thus, when a voice message (`voice`) is received, the overridden handler will be used. Similarly, you can register or change handlers for other types of updates.

## Example of a Processing Scenario
- A user sends the `/start` command to the bot.
- **TelegramHandler** determines which bot (strategy) the current `botName` is associated with and applies the required middleware.
- Then, the `handle()` method of the strategy (e.g., `CaloriesService`) is called.
- Inside `BaseService`, the type of update is determined: `message`.
- `MessageUpdateHandler` looks for which specific Message Handler fits `text` — this is `TextMessageHandler`.
- `TextMessageHandler` checks that the `/start` command is in the list and invokes the corresponding handler (e.g., `StartMessageHandler`).
- As a result, the user receives the response defined in the logic of `StartMessageHandler`.

## Conclusion
- **Flexibility**: Each bot is configured in a separate strategy class and can override/extend the necessary handlers.
- **Modularity**: A unified `TelegramHandler` and middleware are connected to different strategies as needed.
- **Extensibility**: Easily add new commands, new types of messages, and callbacks.

Thanks to this architecture, the project can be scaled without affecting the core structure and can create Telegram bots with different functionalities within a single application.
