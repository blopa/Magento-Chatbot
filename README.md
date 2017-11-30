# Magento Chatbot Module (Telegram, Messenger, Whatsapp, WeChat and Skype)

**Chatbots!** They're all the rage: Telegram has them, Facebook wants them, and it seems like every computer coder wants to make them. But what are they? And why is every company suddenly hot on this new A.I. trend?

<img src="/screenshots/chat.gif?raw=true" width="550px">

> Chatbots are computer programs that you interact with via a chat interface. Using a chatbot is as simple as having a conversation with it. You can ask it questions or give it commands, and it'll respond to you and carry out your actions. Chatbots can be run in any number of chat apps, including Facebook Messenger, your phone's text messaging app, and most others.

**Become part of the chatbots revolution.**
**Download the latest version [here](https://github.com/blopa/Magento-Chatbot/releases/latest).**

## IMPORTANT
We're currently refactoring all our codebase and database structure, so we renamed the `Magento1` folder to `Magento1_deprecated`, the new Magento1 module should be ready before January/2018. To access the deprecated Magento1 version click [here](https://github.com/blopa/Magento-Chatbot/tree/master/Magento1_deprecated)

Please notice that this first version is still not stable, but it should be in December/2017.

## About
This repository is the source code for the Magento Chatbot (Telegram, Messenger, Whatsapp, WeChat and Skype), with this module you can fully integrate your Magento store with the most popular chat apps in the market. This means that by simply installing this module and a few clicks you can have a new way to show and sell your products to your clients.
Very easy to use! Try now, it's FREE.

To use this module you'll need to have SSL enabled in your store, this is a requirement from Facebook and Telegram, not by me.

**For a complete documentation on how to use (specially for advanced wit.ai configuration) access [Magento Chatbot Documentation](https://blopa.github.io/docs/magento_chatbot/).**

**For now only Facebook Messenger is implemented.**

A big thanks to [Eleirbag89](https://github.com/Eleirbag89/) who wrote [this](https://github.com/Eleirbag89/TelegramBotPHP) simple PHP wrapper for Telegram.

## APIs
- [wit.ai](https://github.com/DrMikeyS/FacebookBotPHP/blob/master/FacebookBotPHP.php#L85)
- [Telegram API Wrapper](https://github.com/Eleirbag89/TelegramBotPHP)
- [Facebook API Wrapper](https://github.com/blopa/MessengerBotPHP)
- *Whatsapp API Wrapper* (soon)
- *WeChat API Wrapper* (soon)
- *Skype API Wrapper* (soon)

**PLEASE REPORT ALL BUGS that you find. It's hard to do QA only by myself**

## Features
- wit.ai integration for NLP
- List store categories
- List products from category
- Search for products
- Add product to cart
- Clear cart
- Login/Logout to your account
- List orders
- Track order status
- Reorder
- Send email
- Custom fallback messages

**Currently not working with Configurable Products and products with custom options**

## Languages
- English (US)

## Installation

1. Unpack the extension ZIP file in your Magento root directory
2. Clear the Magento cache: **System > Cache Management**
3. Log out the Magento admin and log back in to clear the ACL list
4. Recompile if you are using the Magento Compiler

## Usage

**For a complete documentation on how to use (specially for advanced wit.ai configuration) access [Magento Chatbot Documentation](https://blopa.github.io/docs/magento_chatbot/).**

Go to **System > General Settings  > Chatbot Settings**

**General Configuration**

- Your Secret Key: This is Your Custom Secret Key Used to Activate/Deactivate The API Webhook
- List Empty Categoies: Enable Listing of Categories With No Products or Unallowed Products
- Enable Log: Enable Log. Log will be at root/var/log/.
- Enable witAI Integration: Enable witAI Integration
- witAI API Key: witAI API Key

**Facebook Configuration**

- Enable Messenger Bot: Enable Messenger Bot
- Unavailability Message: Message To Send When Bot Is Disabled. Leave It Empty To Send No Message. Please Check The Maximum Size For Messages On Telegram API, Otherwise Your Message Might Not Be Sent.
- Page Access Token: Your Page Access Token.
- Messenger Welcome Message: First Message The Bot Will Send To Your Client.
- Messenger Help Message: Message Will be Sent When Customer Asks For Help.
- Messenger About Message: Message Will be Sent When Customer Asks For About.
- Commands List: Code of the commands and it's alias
- Enable Natural Language Processor Replies: Enable Natural Language Processor replies.
- Natural Language Processor Entity Prefix: Natural Language Processor entity prefix is a prefix name to flag that the request is coming from Messenger.
- Natural Language Processor Replies: Replies to be send to the customer whenever matches one of the requirements.

## Screenshot

![ScreenShot](https://raw.githubusercontent.com/blopa/Magento-Chatbot/master/screenshots/facebook_configuration.jpg)

Conversation

![ScreenShot](https://raw.githubusercontent.com/blopa/Magento-Chatbot/master/screenshots/conversation.jpg)

## Release Notes
#### Magento2
- **v1.0.0:**
    - First working version
    - Messenger integration
    - wit.ai integration for NLP
    - List store categories
    - List products from category
    - Search for products
    - Add product to cart
    - Clear cart
    - Login/Logout to your account
    - List orders
    - Track order status
    - Reorder
    - Send email
    - Custom fallback messages
    - Much more
#### Magento1
##### Deprecated versions
- **v0.0.16:**
    - Code improvements
    - Fix flood of messages when disabling bot for human respond
    - [Backend] Add module version to configuration
    - Update wit.ai API version
    - [Backend] Add options/commands to Welcome Message
- **v0.0.15:**
    - First stable version
    - Add option to ignore certain messages using Default Replies
    - Add Customer Chat ID to message for support
    - Add module tables update from previous versions
- **v0.0.14:**
    - Fix problem when setting Telegram Webhook
    - Fix problem when sending message between different chat plataforms
    - Fix small translation problems
    - Code improvements
- **v0.0.13:**
    - Code improvements
    - Add admin commands listing on Telegram
    - Fix problem when receiving 503 from wit.ai
    - [Backend] Add option to disable Bot replies on Facebook (good for when a you need to reply on Page Messages)
    - [Backend] Add option to open Messenger Box with referral
    - [Backend] Add option to write the customer name in a message using "{customername}"
    - Default Replies are now able to send big texts
    - Order listing now uses Facebook Receipt layout
- **v0.0.12:**
    - Fix command alias
    - Fix list categories command glitch
    - Fix some other small glitches
    - Code improvements
    - Add Facebook Live Chat on store frontend
    - [Customer] Speech recognition for Telegram
    - [Backend] Option to enable/disable speech recognition
    - [Backend] Layout improvements
- **v0.0.11:**
    - Fix logout command glitch
    - Fix some other small glitches
    - Code improvements
    - Fix problem when replying a customer on support mode
    - [Backend] Integration with wit.ai
    - [Backend] Use wit.ai as match for a default reply
    - [Customer] Add option do enable/disable receiving promotional messages
- **v0.0.10:**
    - [Backend] Add option to match a 'default reply' as a command
    - [Backend] Add default fallback message
    - [Backend] Add option to disable Telegram inline search
- **v0.0.9:**
    - [Customer] Add inline search for Telegram
    - Add price to product listing on Telegram
    - Fix category buttons listing glitch on Telegram
    - Fix regex validation
    - Fix webhook security issue
    - New webhook URL
- **v0.0.8:**
    - [Backend] Add "starts with", "ends with", "contains", "equals to" and "regex" options to Default Replies
    - [Backend] Better backend layout
    - [Backend] Better way to enable/disable commands
- **v0.0.7:**
    - [Backend] Fix problems with URL
    - [Customer] Add register command
    - Add Chinese translation (big thanks to [leedssheung](https://github.com/leedssheung/))
    - Show command list on "help" instead of "about"
    - Fix buttons size on Telegram when listing categories
    - [Backend] Add option to stop processing when sending a default reply
- **v0.0.6:**
    - [Backend] Better admin descriptions and typos
- **v0.0.5:**
    - [Backend] Add Default Replies
    - [Backend] Add option to unavailability message
    - Better feedback messages
- **v0.0.4:**
    - Fix glitch on order listing
    - Fix glitchs with product search and category listing
    - Better feedback messages
    - [Customer] Add logout command
    - [Admin] Add feature to send message to all customers
    - [Admin] Add option to enable/disable support for a customer
- **v0.0.3:**
    - Limit products/orders listing
    - Add stock validation for listing
    - [Backend] Add option to enable/disable empty categories listing
    - [Customer] Add search by SKU
- **v0.0.2:**
    - Facebook integration
    - [Admin] Cross platform messages with support mode
- **v0.0.1:**
    - Telegram integration
    - First working version


## F.A.Q.
**Q: When the other chatbots integrations are going to be ready?**

A: I'm not sure, I work in my free hours. I'm trying to finish it ASAP. Pull requests are very welcome.

**Q: Can you implement <???> function/bot integration?**

A: I can try. Open a issue and I'll see what I can do.

**Q: Your bot is awesome. How can I help?**

A: Thank you! You can help by codding more features, creating pull requests, or donating using Bitcoin: **1BdL9w4SscX21b2qeiP1ApAFNAYhPj5GgG**

## TODO
- **Integrations:**
    - Whatsapp
    - WeChat
    - Microsoft Bot Framework / Skype
    - ???
- **Features:**
    - Documentation / Tutorials / Videos
    - Add a custom in-store message chat app
    - Save support history
    - Support for audio commands
    - Better uses for wit.ai
    - Force a command for a customer
    - Integrate checkout to Facebook Payment API
    - Add compatibility with configurable products
    - Add compatibility with products with custom options
    - Add "abandoned cart" messages
    - Add `CDATA` and `<tooltip>` to configuration descriptions
    - Add Support
    - Add Promotional Messages
    - Add Enable Facebook Messenger Box
    - Add Welcome Message Options
    - Add Command Listing on Help Command
    - Add Enable Default Replies

## License
MIT License

Copyright (c) 2017 blopa

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

**Free Software, Hell Yeah!**