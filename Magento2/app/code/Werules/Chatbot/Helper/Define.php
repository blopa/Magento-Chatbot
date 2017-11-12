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
    const SECONDS_IN_HOUR = 3600;

    // commands
    const START_COMMAND_ID = 0;
    const LIST_CATEGORIES_COMMAND_ID = 1;
    const SEARCH_COMMAND_ID = 2;
    const LOGIN_COMMAND_ID = 3;
    const LIST_ORDERS_COMMAND_ID = 4;
    const REORDER_COMMAND_ID = 5;
    const ADD_TO_CART_COMMAND_ID = 6;
    const CHECKOUT_COMMAND_ID = 7;
    const CLEAR_CART_COMMAND_ID = 8;
    const TRACK_ORDER_COMMAND_ID = 9;
    const SUPPORT_COMMAND_ID = 10;
    const SEND_EMAIL_COMMAND_ID = 11;
    const CANCEL_COMMAND_ID = 12;
    const HELP_COMMAND_ID = 13;
    const ABOUT_COMMAND_ID = 14;
    const LOGOUT_COMMAND_ID = 15;
    const REGISTER_COMMAND_ID = 16;

    // message content types
    const CONTENT_TEXT = 0;
    const QUICK_REPLY = 1;
}