<?php

namespace App\Clasess;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Keyboard\InlineKeyboard;

class TelegramBotService
{
    protected $telegram;
    protected $botUsername;

    public function __construct()
    {
        // Initialize Telegram Bot API with token from .env
        $this->telegram = new Api('7601754928:AAEqLqnUp77UoFA3Cqg5FhtDrlGUiJMbQEs');

        // Get bot information
        $botInfo = $this->telegram->getMe();
        $this->botUsername = $botInfo->getUsername();
    }

    /**
     * Handle incoming webhook updates
     */
    public function handleWebhook(Update $update)
    {
        try {
            if ($update->getMessage()) {
                $this->handleMessage($update->getMessage());
            } elseif ($update->getCallbackQuery()) {
                $this->handleCallbackQuery($update->getCallbackQuery());
            } else {
                Log::info('Received unhandled update type: ' . $update->getType());
            }
        } catch (\Exception $e) {
            Log::error('Telegram Bot Error: ' . $e->getMessage());
            sleep(2); // Simulate delay as in original code
        }
    }

    /**
     * Handle incoming message
     */
    protected function handleMessage(Message $message)
    {
        $text = $message->text;

        if (!$text) {
            Log::info('Received message of type: ' . $message->type);
            return;
        }

        if (str_starts_with($text, '/')) {
            $this->handleCommand($message);
        } else {
            $this->handleTextMessage($message);
        }
    }

    /**
     * Handle text messages that are not commands
     */
    protected function handleTextMessage(Message $message)
    {
        $text = $message->text;
        Log::info("Received text '$text' in chat {$message->chat->id}");

        switch ($text) {
            case 'как дела':
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Всё хорошо!',
                    'parse_mode' => 'HTML',
                ]);
                break;
            default:
                $this->handleCommand($message, '/start', '');
                break;
        }
    }

    /**
     * Handle commands
     */
    protected function handleCommand(Message $message, string $command = null, string $args = null)
    {
        $text = $message->text;
        if (!$command) {
            $space = strpos($text, ' ') ?: strlen($text);
            $command = strtolower(substr($text, 0, $space));
            $args = trim(substr($text, $space));
        }

        // Check if command is addressed to this bot
        if (($at = strrpos($command, '@')) !== false) {
            $botName = substr($command, $at + 1);
            $command = substr($command, 0, $at);
            if (strcasecmp($botName, $this->botUsername) !== 0) {
                return;
            }
        }

        Log::info("Received command: $command $args");

        switch ($command) {
            case '/как_дела':
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Всё хорошо!',
                    'parse_mode' => 'HTML',
                ]);
                break;

            case '/hi':
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => '🚀Привет, друг!',
                    'parse_mode' => 'HTML',
                ]);
                break;

            case '/start':
                $keyboard = Keyboard::make()->setResizeKeyboard(true)->row(
                    Keyboard::button('photo'),
                    Keyboard::button('audio'),
                    Keyboard::button('/bake'),
                    Keyboard::button('/магазин')
                );
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "<b><u>Bot menu</u></b>:\n/photo      - отправить фото <i>(optionally from an <a href=\"https://picsum.photos/310/200.jpg\">url</a>)</i>\n/audio      - отправить аудио\n/bake       - начало сказки\n/магазин       -магазин",
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                    'reply_markup' => $keyboard,
                ]);
                break;

            case '/photo':
                if (str_starts_with($args, 'http')) {
                    $this->telegram->sendPhoto([
                        'chat_id' => $message->chat->id,
                        'photo' => $args,
                        'caption' => "Source: $args",
                    ]);
                } else {
                    $this->telegram->sendChatAction([
                        'chat_id' => $message->chat->id,
                        'action' => 'upload_photo',
                    ]);
                    sleep(2); // Simulate long task
                    $filePath = storage_path('app/public/bot.gif');
                    if (file_exists($filePath)) {
                        $this->telegram->sendPhoto([
                            'chat_id' => $message->chat->id,
                            'photo' => fopen($filePath, 'r'),
                            'caption' => 'Read https://telegrambots.github.io/book/',
                        ]);
                    } else {
                        $this->telegram->sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => 'Файл не найден. Попробуйте /start',
                        ]);
                    }
                }
                break;

            case '/магазин':
                Log::info("Пользователь {$message->chat->id} открыл магазин");
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(
                        InlineKeyboard::button('Перейти в магазин', url: 'https://example.com/shop'),
                        InlineKeyboard::button('Вернуться к началу', callbackData: '/start')
                    );

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Добро пожаловать в магазин Колобка!  \nПерейдите по ссылке, чтобы купить товары:  \n<a href=\"https://example.com/shop\">Открыть магазин</a>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                break;

            case '/bake':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(
                        InlineKeyboard::button('Убежать', callbackData: '/run_or_stay_choice_run'),
                        InlineKeyboard::button('Остаться', callbackData: '/run_or_stay_choice_stay')
                    )
                    ->row(
                        InlineKeyboard::button('Магазин Деда и Бабки', callbackData: '/магазин')
                    );

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Жил-был старик со старухой. Они испекли румяного колобка!  \nЧто делать колобку?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);

                $this->sendPhoto($message->chat->id, 'public/a/e3cc8fcf-99d4-4bd1-bc25-75b1918b3c62.jpg', 'Привет');
                break;

            case '/run_or_stay_choice_stay':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $this->telegram->sendMessage(['chat_id' => $message->chat->id,
                    'text' => "Колобок решил остаться. Дед с бабкой обрадовались, но потом... съели его!  \n<b>Финал 1: Съеден Дедом и Бабкой.</b>  \nПопробовать снова? Нажми /bake",
                    'parse_mode' => 'HTML',
                ]);
                $this->sendPhoto($message->chat->id, 'public/a/e3cc8fcf-99d4-4bd1-bc25-75b1918b3c62.jpg', 'Привет');
                break;

            case '/run_or_stay_choice_run':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(
                        InlineKeyboard::button('Спеть песенку', callbackData: '/meet_rabbit_choice_sing'),
                        InlineKeyboard::button('Угрожать', callbackData: '/meet_rabbit_choice_threaten')
                    )
                    ->row(
                        InlineKeyboard::button('Магазин зайца', callbackData: '/магазин')
                    );

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок покатился по дорожке, напевая:  \n<i>\"Я колобок, колобок, по дорожке я иду, от деда, от бабки убегу!\"</i>  \nВдруг навстречу ему Заяц!  \n— Стой, колобок, хочу тебя съесть!  \nЧто делать?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);

                $this->sendAudio($message->chat->id, 'public/a/kolobok.mp3', 'хай');
                $this->sendPhoto($message->chat->id, 'public/a/1c616baa-2b7d-4bf1-9e9a-783f596ee669.jpg', 'Привет');
                break;

            case '/meet_rabbit_choice_sing':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(InlineKeyboard::button('Продолжить', callbackData: '/meet_wolf'));

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок запел свою песенку. Заяц засмеялся:  \n— Ладно, катайся дальше, весёлый ты!  \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                break;

            case '/meet_rabbit_choice_threaten':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(InlineKeyboard::button('Продолжить', callbackData: '/meet_wolf'));

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "— А не то я тебя побью! — сказал колобок.  \nЗаяц удивился, фыркнул и ускакал прочь.  \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                break;

            case '/meet_wolf':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(
                        InlineKeyboard::button('Спеть песенку', callbackData: '/meet_wolf_choice_sing'),
                        InlineKeyboard::button('Спрятаться', callbackData: '/meet_wolf_choice_hide')
                    )
                    ->row(
                        InlineKeyboard::button('Магазин волка', callbackData: '/магазин')
                    );

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,'text' => "Из кустов выскочил Волк:  \n— Ого, вкусный колобок!  \nЧто делать?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                $this->sendPhoto($message->chat->id, 'public/a/photo_5208688783720049056_x.jpg', 'Привет');
                break;

            case '/meet_wolf_choice_sing':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(InlineKeyboard::button('Продолжить', callbackData: '/meet_bear'));

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок запел. Волк заулыбался:  \n— Хорошая песня, катайся дальше!  \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                $this->sendAudio($message->chat->id, 'public/a/wolf.mp3', 'кхм-кхм, даров!');
                break;

            case '/meet_wolf_choice_hide':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(InlineKeyboard::button('Продолжить', callbackData: '/meet_bear'));

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок юркнул в траву и затаился. Волк не нашёл его и ушёл.  \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                break;

            case '/meet_bear':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(
                        InlineKeyboard::button('Похвастаться', callbackData: '/meet_bear_choice_boast'),
                        InlineKeyboard::button('Угостить пирогом', callbackData: '/meet_bear_choice_treat')
                    )
                    ->row(
                        InlineKeyboard::button('Магазин медведя', callbackData: '/магазин')
                    );

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "На пути появился Медведь:  \n— Кто это катится? Хочу попробовать!  \nЧто делать?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                $this->sendPhoto($message->chat->id, 'public/a/5717e477-db54-4f81-a508-0910f4c5614d.jpg', 'Привет');
                $this->sendAudio($message->chat->id, 'public/a/bear.wav', 'йоу-йоу! го репчикс');
                break;

            case '/meet_bear_choice_boast':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(InlineKeyboard::button('Продолжить', callbackData: '/meet_fox'));

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "— Я самый быстрый и умный колобок! Никто меня не поймает! — сказал колобок.  \nМедведь буркнул:  \n— Ну, раз такой крутой, катайся! — и ушёл.  \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                break;

            case '/meet_bear_choice_treat':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(InlineKeyboard::button('Продолжить', callbackData: '/meet_fox'));

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок предложил:  \n— Давай поделюсь кусочком начинки!  \nМедведь обрадовался:  \n— Вот это да! Давай дружить!  \nТеперь они друзья. Колобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                break;

            case '/meet_fox':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $inlineKeyboard = InlineKeyboard::make()
                    ->row(
                        InlineKeyboard::button('Спеть песенку', callbackData: '/meet_fox_choice_sing'),
                        InlineKeyboard::button('Сбежать', callbackData: '/meet_fox_choice_run'),
                        InlineKeyboard::button('Дать интервью', callbackData: '/meet_fox_choice_interview')
                    )
                    ->row(
                        InlineKeyboard::button('Магазин лисы', callbackData: '/магазин')
                    );

                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок встретил Лису. Она мило улыбнулась:  \n— Ой, какой славный колобок! Спой мне песенку!  \nЧто делать?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inlineKeyboard,
                ]);
                $this->sendAudio($message->chat->id, 'public/a/lisa.wav', 'мммм, какой ты');
                $this->sendPhoto($message->chat->id, 'public/a/501e8981-3cf1-4243-a802-e8ade6e35ad7.jpg', 'Привет');
                break;

            case '/meet_fox_choice_sing':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок запел. Лиса слушала, а потом... схватила его!  \n<b>Финал 2: Лиса съедает.</b>  \nПопробовать снова? Нажми /bake",
                    'parse_mode' => 'HTML',
                ]);
                break;

            case '/meet_fox_choice_run':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок рванул в сторону и укатился в густой лес. Лиса осталась ни с чем.  \n<b>Финал 3: Уходит в лес.</b>  \nПопробовать снова? Нажми /bake",
                    'parse_mode' => 'HTML',
                ]);
                break;

            case '/meet_fox_choice_interview':
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing',
                ]);
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "— Хочешь узнать мою историю? — сказал колобок.  \nЛиса начала записывать. Видео стало вирусным!  \n<b>Финал 4: Колобок становится блогером.</b>  \nПопробовать снова? Нажми /bake",
                    'parse_mode' => 'HTML',
                ]);
                $this->sendPhoto($message->chat->id, 'public/a/9b18bee8-8f14-4d44-8b5d-047828ae300f.jpg', 'Хай');
                break;

            default:
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Я бот-сказка про Колобка!  \nНачни с команды /bake, чтобы отправиться в приключение!",'parse_mode' => 'HTML',
                ]);
                break;
        }
    }

    /**
     * Handle callback queries
     */
    protected function handleCallbackQuery(CallbackQuery $callbackQuery)
    {
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => "You selected {$callbackQuery->data}",
        ]);

        $this->handleCommand($callbackQuery->message, $callbackQuery->data, '');
    }

    /**
     * Send photo to chat
     */
    protected function sendPhoto(int $chatId, string $path, string $caption)
    {
        $filePath = storage_path("app/$path");
        if (file_exists($filePath)) {
            $this->telegram->sendChatAction([
                'chat_id' => $chatId,
                'action' => 'upload_photo',
            ]);
            $this->telegram->sendPhoto([
                'chat_id' => $chatId,
                'photo' => fopen($filePath, 'r'),
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Неизвестная команда. Попробуйте /start',
                'parse_mode' => 'HTML',
            ]);
        }
    }

    /**
     * Send audio to chat
     */
    protected function sendAudio(int $chatId, string $path, string $caption)
    {
        $filePath = storage_path("app/$path");
        if (file_exists($filePath)) {
            $this->telegram->sendChatAction([
                'chat_id' => $chatId,
                'action' => 'upload_audio',
            ]);
            $this->telegram->sendAudio([
                'chat_id' => $chatId,
                'audio' => fopen($filePath, 'r'),
                'caption' => $caption,
                'title' => $caption,
                'parse_mode' => 'HTML',
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Ошибка при отправке аудио. Попробуйте /start',
                'parse_mode' => 'HTML',
            ]);
        }
    }

    /**
     * Send invoice for shop
     */
    protected function sendGrandparentsShop(int $chatId)
    {
        $this->telegram->sendInvoice([
            'chat_id' => $chatId,
            'title' => 'Магазин Деда и Бабки',
            'description' => 'Традиционные товары от создателей Колобка!',
            'payload' => 'grandparents_shop',
            'provider_token' => env('TELEGRAM_PAYMENT_PROVIDER_TOKEN'),
            'currency' => 'RUB',
            'photo_url' => 'https://picsum.photos/512/512?random=1',
            'photo_size' => 512,
            'photo_width' => 512,
            'photo_height' => 512,
            'prices' => [
                ['label' => 'Мука для колобков', 'amount' => 500],
                ['label' => 'Рецепт колобка', 'amount' => 1000],
                ['label' => 'Печка для выпечки', 'amount' => 5000],
            ],
            'need_name' => true,
            'need_email' => true,
            'need_phone_number' => true,
            'is_flexible' => true,
            'reply_markup' => InlineKeyboard::make()->row(
                InlineKeyboard::button('Оплатить', url: 'https://example.com/payment')
            ),
        ]);

        Log::info('Инвойс магазина Деда и Бабки отправлен!');
    }
}
