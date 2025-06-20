<?php


use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

Route::any('/telegram/webhook', function () {
    $update = Telegram::getWebhookUpdate();
        Log::log(print_r($update, true));
    app(TelegramBotService::class)->handleWebhook($update);
    return response('OK', 200);
});
Route::get('/', function () {
    return "ok";
});
