<?php
/**
 * Magento Chatbot Integration
 * Copyright (C) 2018
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

class Werules_Chatbot_Helper_Define
{
    public $MESSENGER_INT = 0;
    public $TELEGRAM_INT = 1;
    public $NOT_PROCESSED = 0;
    public $PROCESSING = 1;
    public $PROCESSED = 2;
    public $INCOMING = 0;
    public $OUTGOING = 1;
    public $DISABLED = 0;
    public $ENABLED = 1;
    public $NOT_LOGGED = 0;
    public $NOT_ADMIN = 0;
    public $ADMIN = 1;
    public $LOGGED = 1;
    public $SECONDS_IN_HOUR = 3600;
    public $SECONDS_IN_MINUTE = 60;
    public $DEFAULT_MIN_CONFIDENCE = 0.7;
    public $BREAK_LINE = '\n'; // chr(10)
    public $QUEUE_PROCESSING_LIMIT = 180;//$SECONDS_IN_MINUTE * 3;
    public $NOT_SENT = 0;
    public $SENT = 1;

    // commands
    public $START_COMMAND_ID = 0;
    public $LIST_CATEGORIES_COMMAND_ID = 1;
    public $SEARCH_COMMAND_ID = 2;
    public $LOGIN_COMMAND_ID = 3;
    public $LIST_ORDERS_COMMAND_ID = 4;
    public $REORDER_COMMAND_ID = 5;
    public $ADD_TO_CART_COMMAND_ID = 6;
    public $CHECKOUT_COMMAND_ID = 7;
    public $CLEAR_CART_COMMAND_ID = 8;
    public $TRACK_ORDER_COMMAND_ID = 9;
    public $SUPPORT_COMMAND_ID = 10;
    public $SEND_EMAIL_COMMAND_ID = 11;
    public $CANCEL_COMMAND_ID = 12;
    public $HELP_COMMAND_ID = 13;
    public $ABOUT_COMMAND_ID = 14;
    public $LOGOUT_COMMAND_ID = 15;
    public $REGISTER_COMMAND_ID = 16;
    public $LIST_MORE_COMMAND_ID = 17;
    public $LAST_COMMAND_DETAILS_DEFAULT = '{"last_command_parameter":"","last_command_text":"","last_conversation_state":0,"last_listed_quantity":0}';
//    array(
//        'last_command_parameter' => '',
//        'last_command_text' => '',
//        'last_conversation_state' => 0,
//        'last_listed_quantity' => 0,
//    );
    public $CURRENT_COMMAND_DETAILS_DEFAULT = '[]';
//    json_encode(array())

    // message content types
    public $CONTENT_TEXT = 0;
    public $QUICK_REPLY = 1;
    public $IMAGE_WITH_TEXT = 2;
    public $IMAGE_WITH_OPTIONS = 3;
    public $RECEIPT_LAYOUT = 4;
    public $LIST_WITH_IMAGE = 5;
    public $TEXT_WITH_OPTIONS = 6;
    public $NO_REPLY_MESSAGE = 7;

    // conversation states
    public $CONVERSATION_STARTED = 0;
    public $CONVERSATION_LIST_CATEGORIES = 1;
    public $CONVERSATION_SEARCH = 2;
    public $CONVERSATION_EMAIL = 3;
    public $CONVERSATION_TRACK_ORDER = 4;
    public $CONVERSATION_SUPPORT = 5;

    // API
    public $MAX_MESSAGE_ELEMENTS = 7;

    // MESSAGE QUEUE MODES
    public $QUEUE_NONE = 0;
    public $QUEUE_SIMPLE_RESTRICTIVE = 1;
    public $QUEUE_RESTRICTIVE = 2;
    public $QUEUE_NON_RESTRICTIVE = 3;

    public $DONT_CLEAR_MESSAGE_QUEUE = 0;
    public $CLEAR_MESSAGE_QUEUE = 1;

    // DEFAULT REPLIES MODES
    public $EQUALS_TO = 0;
    public $STARTS_WITH = 1;
    public $ENDS_WITH = 2;
    public $CONTAINS = 3;
    public $MATCH_REGEX = 4;
}