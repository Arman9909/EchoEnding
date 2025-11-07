<?php

namespace App\Clasess;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\CallbackQuery;

class TelegramBotService
{
    protected $telegram;
    protected $botUsername;

    public function __construct()
    {
        $this->telegram = new Api(env('7601754928:AAGElcJKUU1NctCCDs_5NXn41N7VhQMlLfk'));
        $botInfo = $this->telegram->getMe();
        $this->botUsername = $botInfo->getUsername();
    }

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
            sleep(2);
        }
    }

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

    protected function handleCommand(Message $message, string $command = null, string $args = null)
    {
        $text = $message->text;
        if (!$command) {
            $space = strpos($text, ' ') ?: strlen($text);
            $command = strtolower(substr($text, 0, $space));
            $args = trim(substr($text, $space));
        }

        if (($at = strrpos($command, '@')) !== false) {
            $botName = substr($command, $at + 1);
            $command = substr($command, 0, $at);
            if (strcasecmp($botName, $this->botUsername) !== 0) {
                return;
            }
        }

        Log::info("Received command: $command $args");

        switch ($command) {
            case '/start':
                $keyboard = [
                    'keyboard' => [
                        [['text' => 'Приступить к истории']],

                    ],
                    'resize_keyboard' => true
                ];
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "<b>Добро пожаловать в сервис альтернативных концовок для книг Echo-Ending!</b> 🥯\n\nЗдесь вы сможете подробно познакомиться с альтернативной концовкой для сказки Колобок!\n\nГотовы отправиться в увлекательное приключение, где Колобок катится по лесу и встречает разных зверей? В этой версии сказки <i>вы</i> выбираете, как закончится история! Нажмите <b>Начать</b>, чтобы погрузиться в мир альтернативных концовок.\n\n<b>Команды:</b>\n/Начать     - начать сказку\n/bake       - испечь нового Колобка\n/магазин    - посетить магазин",
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                    'reply_markup' => json_encode($keyboard),
                ]);
                // Send Kolobok image
                $this->sendPhoto(
                    $message->chat->id,
                    'public/images/kolobok.jpg', // Adjust path to your image in Storage
                    'Вот он, наш Колобок, готовый к приключениям! 🥯'
                );
                break;

            case '/магазин':
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Перейти в магазин', 'url' => 'https://example.com/shop'],
                            ['text' => 'Вернуться к началу', 'callback_data' => '/start']
                        ]
                    ]
                ];
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Добро пожаловать в магазин Echo-Ending!  \nПерейдите по ссылке, чтобы купить товары:  \n<a href=\"https://example.com/shop\">Открыть магазин</a>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard),
                ]);
                break;

            case '/bake':
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Убежать', 'callback_data' => '/run_or_stay_choice_run'],
                            ['text' => 'Остаться', 'callback_data' => '/run_or_stay_choice_stay']
                        ],
                        [
                            ['text' => 'Магазин Деда и Бабки', 'callback_data' => '/магазин']
                        ]
                    ]
                ];
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Жил-был старик со старухой. Они испекли румяного колобка!  \nЧто делать колобку?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard),
                ]);

                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'parse_mode' => 'HTML',
                    'action' => 'typing'
                ]);


                // Формируем инлайн-клавиатуру с кнопкой "/bake"
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Попробовать снова?', 'callback_data' => '/bake']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок решил остаться. Дед с бабкой обрадовались, но потом... съели его! \n<b>Финал 1: Съеден Дедом и Бабкой.</b> \nПопробовать снова? Нажми /bake",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);

                // Отправляем действие "загружает фото"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'upload_photo'
                ]);

                // Отправляем фото
                $this->telegram->sendPhoto([
                    'chat_id' => $message->chat->id,
                    'photo' => 'ded_i_babka.jpg', // Замените на реальный URL или путь к файлу
                    'caption' => 'Привет'
                ]);
                break;


            case "/run_or_stay_choice_run":
                // Отправляем действие "печатает"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing'
                ]);

                // Формируем инлайн-клавиатуру
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Спеть песенку', 'callback_data' => '/meet_rabbit_choice_sing'],
                            ['text' => 'Угрожать', 'callback_data' => '/meet_rabbit_choice_threaten']
                        ],
                        [
                            ['text' => 'Магазин зайца', 'callback_data' => '/магазин']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок покатился по дорожке, напевая: \n<i>\"Я колобок, колобок, по дорожке я иду, от деда, от бабки убегу!\"</i> \nВдруг навстречу ему Заяц! \n— Стой, колобок, хочу тебя съесть! \nЧто делать?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);

                // Отправляем действие "загружает фото"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'upload_photo'
                ]);

                // Отправляем аудио
                $this->telegram->sendAudio([
                    'chat_id' => $message->chat->id,
                    'audio' => 'kolobok.mp3', // Замените на реальный URL или путь к файлу
                    'caption' => 'хай'
                ]);

                // Отправляем действие "записывает голос"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'record_audio'
                ]);

                // Отправляем фото
                $this->telegram->sendPhoto([
                    'chat_id' => $message->chat->id,
                    'photo' => 'zayac.jpg', // Замените на реальный URL или путь к файлу
                    'caption' => 'Привет'
                ]);
                break;
            case "/meet_rabbit_choice_sing":
                // Отправляем действие "печатает"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing'
                ]);

                // Формируем инлайн-клавиатуру
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Продолжить', 'callback_data' => '/meet_wolf']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок запел свою песенку. Заяц засмеялся: \n— Ладно, катайся дальше, весёлый ты! \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);
                break;

            case "/meet_rabbit_choice_threaten":
                // Отправляем действие "печатает"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing'
                ]);

                // Формируем инлайн-клавиатуру
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Продолжить', 'callback_data' => '/meet_wolf']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "— А не то я тебя побью! — сказал колобок. \nЗаяц удивился, фыркнул и ускакал прочь. \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);
                break;

            case "/meet_wolf":
                // Отправляем действие "печатает"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing'
                ]);

                // Формируем инлайн-клавиатуру
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Спеть песенку', 'callback_data' => '/meet_wolf_choice_sing'],
                            ['text' => 'Спрятаться', 'callback_data' => '/meet_wolf_choice_hide']
                        ],
                        [
                            ['text' => 'Магазин волка', 'callback_data' => '/магазин']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Из кустов выскочил Волк: \n— Ого, вкусный колобок! \nЧто делать?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);

                // Отправляем действие "загружает фото"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'upload_photo'
                ]);

                // Отправляем фото
                $this->telegram->sendPhoto([
                    'chat_id' => $message->chat->id,
                    'photo' => 'wolf.jpg', // Замените на реальный URL или путь к файлу
                    'caption' => 'Привет'
                ]);
                break;

            case "/meet_wolf_choice_sing":
                // Отправляем действие "печатает"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing'
                ]);

                // Формируем инлайн-клавиатуру
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Продолжить', 'callback_data' => '/meet_bear']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок запел. Волк заулыбался: \n— Хорошая песня, катайся дальше! \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);

                // Отправляем действие "записывает голос"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'record_audio'
                ]);

                // Отправляем аудио
                $this->telegram->sendAudio([
                    'chat_id' => $message->chat->id,
                    'audio' => 'wolf.mp3', // Замените на реальный URL или путь к файлу
                    'caption' => 'кхм-кхм, даров!'
                ]);
                break;

            case "/meet_wolf_choice_hide":
                // Отправляем действие "печатает"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing'
                ]);

                // Формируем инлайн-клавиатуру
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Продолжить', 'callback_data' => '/meet_bear']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Колобок юркнул в траву и затаился. Волк не нашёл его и ушёл. \nКолобок покатился дальше.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);
                break;

            case "/meet_bear":
                // Отправляем действие "печатает"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'typing'
                ]);

                // Формируем инлайн-клавиатуру
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Похвастаться', 'callback_data' => '/meet_bear_choice_boast'],
                            ['text' => 'Угостить пирогом', 'callback_data' => '/meet_bear_choice_treat']
                        ],
                        [
                            ['text' => 'Магазин медведя', 'callback_data' => '/магазин']
                        ]
                    ]
                ];

                // Отправляем сообщение
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "На пути появился Медведь: \n— Кто это катится? Хочу попробовать! \nЧто делать?",
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);

                // Отправляем действие "загружает фото"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'upload_photo'
                ]);

                // Отправляем фото
                $this->telegram->sendPhoto([
                    'chat_id' => $message->chat->id,
                    'photo' => 'bear.jpg', // Замените на реальный URL или путь к файлу
                    'caption' => 'Привет'
                ]);

                // Отправляем действие "записывает голос"
                $this->telegram->sendChatAction([
                    'chat_id' => $message->chat->id,
                    'action' => 'record_audio'
                ]);

                // Отправляем аудио
                $this->telegram->sendAudio([
                    'chat_id' => $message->chat->id,
                    'audio' => 'bear.wav', // Замените на реальный URL или путь к файлу
                    'caption' => 'йоу-йоу! го репчикс'
                ]);
                break;
            default:
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => "Неизвестная команда. Попробуй /start",
                ]);
                break;

        }




        $command = $message->text; // Предполагаем, что команда приходит в $message->text

        if (!isset($message->chat->id)) {
            // Обработка ошибки, если chat_id отсутствует
            return;
        }

        if ($command === '/meet_bear_choice_boast') {
            // Отправляем действие "печатает"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'typing'
            ]);

            // Формируем инлайн-клавиатуру
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Продолжить', 'callback_data' => '/meet_fox']
                    ]
                ]
            ];

            $replyMarkup = json_encode($inlineKeyboard);
            if ($replyMarkup === false) {
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Ошибка формирования клавиатуры.'
                ]);
                return;
            }

            // Отправляем сообщение
            $this->telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "— Я самый быстрый и умный колобок! Никто меня не поймает! — сказал колобок. \nМедведь буркнул: \n— Ну, раз такой крутой, катайся! — и ушёл. \nКолобок покатился дальше.",
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);
        } elseif ($command === '/meet_bear_choice_treat') {
            // Отправляем действие "печатает"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'typing'
            ]);

            // Формируем инлайн-клавиатуру
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Продолжить', 'callback_data' => '/meet_fox']
                    ]
                ]
            ];

            $replyMarkup = json_encode($inlineKeyboard);
            if ($replyMarkup === false) {
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Ошибка формирования клавиатуры.'
                ]);
                return;
            }

            // Отправляем сообщение
            $this->telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "Колобок предложил: \n— Давай поделюсь кусочком начинки! \nМедведь обрадовался: \n— Вот это да! Давай дружить! \nТеперь они друзья. Колобок покатился дальше.",
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);
        } elseif ($command === '/meet_fox') {
            // Отправляем действие "печатает"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'typing'
            ]);

            // Формируем инлайн-клавиатуру
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Спеть песенку', 'callback_data' => '/meet_fox_choice_sing'],
                        ['text' => 'Сбежать', 'callback_data' => '/meet_fox_choice_run'],
                        ['text' => 'Дать интервью', 'callback_data' => '/meet_fox_choice_interview']
                    ],
                    [
                        ['text' => 'Магазин лисы', 'callback_data' => '/магазин']
                    ]
                ]
            ];

            $replyMarkup = json_encode($inlineKeyboard);
            if ($replyMarkup === false) {
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Ошибка формирования клавиатуры.'
                ]);
                return;
            }

            // Отправляем сообщение
            $this->telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "Колобок встретил Лису. Она мило улыбнулась: \n— Ой, какой славный колобок! Спой мне песенку! \nЧто делать?",
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);

            // Отправляем действие "записывает голос"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'record_audio'
            ]);

            // Отправляем аудио
            $this->telegram->sendAudio([
                'chat_id' => $message->chat->id,
                'audio' => 'lisa.wav', // Замените на реальный URL или путь к файлу
                'caption' => 'мммм, какой ты'
            ]);

            // Отправляем действие "загружает фото"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'upload_photo'
            ]);

            // Отправляем фото
            $this->telegram->sendPhoto([
                'chat_id' => $message->chat->id,
                'photo' => 'lisa.jpg', // Замените на реальный URL или путь к файлу
                'caption' => 'Привет'
            ]);
        } elseif ($command === '/meet_fox_choice_sing') {
            // Отправляем действие "печатает"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'typing'
            ]);

            // Формируем инлайн-клавиатуру
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Попробовать снова?', 'callback_data' => '/bake']
                    ]
                ]
            ];

            $replyMarkup = json_encode($inlineKeyboard);
            if ($replyMarkup === false) {
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Ошибка формирования клавиатуры.'
                ]);
                return;
            }

            // Отправляем message
            $this->telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "Колобок запел. Лиса слушала, а потом... схватила его! \n<b>Финал 2: Лиса съедает.</b> \nПопробовать снова? Нажми /bake",
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);
        } elseif ($command === '/meet_fox_choice_run') {
            // Отправляем действие "печатает"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'typing'
            ]);

            // Формируем инлайн-клавиатуру
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Попробовать снова?', 'callback_data' => '/bake']
                    ]
                ]
            ];

            $replyMarkup = json_encode($inlineKeyboard);
            if ($replyMarkup === false) {
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Ошибка формирования клавиатуры.'
                ]);
                return;
            }

            // Отправляем сообщение
            $this->telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "Колобок рванул в сторону и укатился в густой лес. Лиса осталась ни с чем. \n<b>Финал 3: Уходит в лес.</b> \nПопробовать снова? Нажми /bake",
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);
        } elseif ($command === '/meet_fox_choice_interview') {
            // Отправляем действие "печатает"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'typing'
            ]);

            // Формируем инлайн-клавиатуру
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Попробовать снова?', 'callback_data' => '/bake']
                    ]
                ]
            ];

            $replyMarkup = json_encode($inlineKeyboard);
            if ($replyMarkup === false) {
                $this->telegram->sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => 'Ошибка формирования клавиатуры.'
                ]);
                return;
            }

            // Отправляем сообщение
            $this->telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "— Хочешь узнать мою историю? — сказал колобок. \nЛиса начала записывать. Видео стало вирусным! \n<b>Финал 4: Колобок становится блогером.</b> \nПопробовать снова? Нажми /bake",
                'parse_mode' => 'HTML',
                'reply_markup' => $replyMarkup
            ]);

            // Отправляем действие "загружает фото"
            $this->telegram->sendChatAction([
                'chat_id' => $message->chat->id,
                'action' => 'upload_photo'
            ]);

            // Отправляем фото
            $this->telegram->sendPhoto([
                'chat_id' => $message->chat->id,
                'photo' => 'kolobok_operator.jpg', // Замените на реальный URL или путь к файлу
                'caption' => 'Хай'
            ]);
        } else {
            // Отправляем сообщение по умолчанию
            $this->telegram->sendMessage([
                'chat_id' => $message->chat->id,
                'text' => "Я бот-сказка про Колобка! \nНачни с команды /bake, чтобы отправиться в приключение!",
                'parse_mode' => 'HTML'
            ]);
        }
        // остальные команды аналогично (все клавиатуры оформить через массив)

    }

    protected function handleCallbackQuery(CallbackQuery $query)
    {
        $message = $query->getMessage();
        $this->handleCommand($message, $query->getData(), '');
    }

    protected function sendPhoto($chatId, $path, $caption = '')
    {
        $this->telegram->sendPhoto([
            'chat_id' => $chatId,
            'photo' => Storage::path($path),
            'caption' => $caption,
        ]);
    }

    protected function sendAudio($chatId, $path, $caption = '')
    {
        $this->telegram->sendAudio([
            'chat_id' => $chatId,
            'audio' => Storage::path($path),
            'caption' => $caption,
        ]);
    }
}
