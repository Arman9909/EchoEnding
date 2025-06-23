<?php


use App\Clasess\TelegramBotService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

Route::any('/telegram/webhook', function () {
    Log::info(print_r('hello', true));
    $update = Telegram::getWebhookUpdate();
        Log::info(print_r($update, true));
    app(TelegramBotService::class)->handleWebhook($update);
    return response('OK', 200);
});
Route::get('/', function () {
    return "ok";
});
