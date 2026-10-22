<?php

namespace App\Contracts;

use App\Support\Messaging\MessageResult;
use App\Support\Messaging\OutgoingMessage;

/**
 * A way of delivering a text message to a patient.
 *
 * The template ships with WhatsAppLinkChannel (opens a prefilled wa.me chat, no paid API).
 * To use a provider (SMS gateway, WhatsApp Business API…), implement this interface and
 * register it in config/clinic.php under messaging.channels, then pick it in .env:
 *   CLINIC_MESSAGING_CHANNEL=my_provider
 */
interface MessageChannel
{
    /** Unique key stored in notification_logs.channel. */
    public function name(): string;

    /** True when the message is delivered by the server; false when the user must click a link. */
    public function sendsAutomatically(): bool;

    public function send(OutgoingMessage $message): MessageResult;
}
