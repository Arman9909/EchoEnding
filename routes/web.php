<?php
use App\Services\TelegramBotService;
use Telegram\Bot\Laravel\Facades\Telegram;

Route::post('/telegram/webhook', function () {
    $update = Telegram::getWebhookUpdate();
    app(TelegramBotService::class)->handleWebhook($update);
    return response('OK', 200);
});
