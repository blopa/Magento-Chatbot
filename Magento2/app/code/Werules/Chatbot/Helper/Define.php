<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2017
 *
 * This file is part of Werules/Chatbot.
 *
 * Werules/Chatbot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Werules\Chatbot\Helper;


class Define
{
    const MESSENGER_INT = 0;
    const TELEGRAM_INT = 1;
    const NOT_PROCESSED = 0;
    const PROCESSING = 1;
    const PROCESSED = 2;
    const INCOMING = 0;
    const OUTGOING = 1;
    const DISABLED = 0;
    const ENABLED = 1;
    const CONVERSATION_STARTED = 0;
    const CONTENT_TEXT = 0;
    const SECONDS_IN_HOUR = 3600;
}