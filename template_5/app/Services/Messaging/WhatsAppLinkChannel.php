<?php

namespace App\Services\Messaging;

use App\Contracts\MessageChannel;
use App\Support\Messaging\MessageResult;
use App\Support\Messaging\OutgoingMessage;

/**
 * Free "click to send": builds a wa.me link with the message prefilled.
 * The receptionist's WhatsApp (web or phone) opens and they press send.
 */
class WhatsAppLinkChannel implements MessageChannel
{
    public function name(): string
    {
        return 'whatsapp_link';
    }

    public function sendsAutomatically(): bool
    {
        return false;
    }

    public function send(OutgoingMessage $message): MessageResult
    {
        return MessageResult::link('https://wa.me/'.$message->to.'?text='.rawurlencode($message->body));
    }
}
