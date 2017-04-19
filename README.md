# Magento Chatbot Module (Telegram, Messenger, Whatsapp, WeChat and Skype)

**Chatbots!** They're all the rage: Telegram has them, Facebook wants them, and it seems like every computer coder wants to make them. But what are they? And why is every company suddenly hot on this new A.I. trend?

<img src="/screenshots/chat.gif?raw=true" width="550px">

> Chatbots are computer programs that you interact with via a chat interface. Using a chatbot is as simple as having a conversation with it. You can ask it questions or give it commands, and it'll respond to you and carry out your actions. Chatbots can be run in any number of chat apps, including Facebook Messenger, your phone's text messaging app, and most others.

**Become part of the chatbots revolution.**
**Download the latest version [here](https://github.com/blopa/Magento-Chatbot/releases/latest). Or get it via Magento Connect https://www.magentocommerce.com/magento-connect/chatbot-integration.html**

## About
This repository is the source code for the Magento Chatbot (Telegram, Messenger, Whatsapp, WeChat and Skype), with this module you can fully integrate your Magento store with the most popular chat apps in the market. This means that by simply installing this module and a few clicks you can have a new way to show and sell your products to your clients.
Very easy to use! Try now, it's FREE.

To use this module you'll need to have SSL enabled in your store, this is a requirement from Facebook and Telegram, not by me.

**For now only Telegram and Facebook Messenger is implemented, try it out Telegram at [@MyChatbotStoreBot](https://telegram.me/MyChatbotStoreBot).**

A big thanks to [Eleirbag89](https://github.com/Eleirbag89/) who wrote [this](https://github.com/Eleirbag89/TelegramBotPHP) simple PHP wrapper for Telegram.

## APIs
- [wit.ai](https://github.com/DrMikeyS/FacebookBotPHP/blob/master/FacebookBotPHP.php#L85)
- [Telegram API Wrapper](https://github.com/Eleirbag89/TelegramBotPHP)
- [Facebook API Wrapper](https://github.com/blopa/MessengerBotPHP)
- *Whatsapp API Wrapper* (soon)
- *WeChat API Wrapper* (soon)

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
- Send message to support
- Reply customer support messages from you favorite chat messenger
- Send message to all customers (promotion messages, etc)
- Force exit customer from support mode
- Block a customer for using support mode
- Use Telegram to receive and reply messages from Facebook
- Set custom reply messages for predetermined text or regex
- Custom fallback messages

**Currently not working with Configurable Products and products with custom options**

## Languages
- English (US)
- Portuguese (BR)
- Chinese (Greater China region)

## Installation

1. Unpack the extension ZIP file in your Magento root directory
2. Clear the Magento cache: **System > Cache Management**
3. Log out the Magento admin and log back in to clear the ACL list
4. Recompile if you are using the Magento Compiler

**Attention to blocks from other modules, it may overwrite the webhook URL, which must NOT contain any blocks.**

If you find yourself in this situation, exclude the block by adding ```<remove name="BLOCK_NAME" />``` at ```Magento-Root/app/design/frontend/base/default/layout/werules_chatbot.xml```

## Usage

Go to **System > General Settings  > Chatbot Settings**

**General Configuration**

- Your Secret Key: This is Your Custom Secret Key Used to Activate/Deactivate The API Webhook
- Master Support Group: Choose to receive all support messages in one single message app (currently not developed)
- Forward Unknown Messages to Support: Automatically Enable Support if Customer Types a Message That The Bot Dosen't Understand
- List Empty Categoies: Enable Listing of Categories With No Products or Unallowed Products
- Enable Log: Enable Log. Log will be at root/var/log/.

**wit.ai Configuration**

- Enable witAI Integration: Enable witAI Integration
- witAI API Key: witAI API Key
- witAI Confidence Percentage: witAI Confidence Percentage

**Telegram Configuration**

- Enable Telegram Bot: Enable Telegram Bot
- Unavailability Message: Message To Send When Bot Is Disabled. Leave It Empty To Send No Message. Please Check The Maximum Size For Messages On Telegram API, Otherwise Your Message Might Not Be Sent.
- Telegram Bot API Key: Your Telegram Bot API Key.
- Telegram Support Group ID: ID of Group that the support messages will be forwarded. e.g. g123456789
- Telegram Welcome Message: First Message The Bot Will Send To Your Client.
- Telegram Help Message: Message Will be Sent When Customer Asks For Help.
- Telegram About Message: Message Will be Sent When Customer Asks For About.
- Enable Command Listing On Help: Enable Command Listing When Customer Ask For Help
- Enable Command Listing On Error: Enable Command Listing When The Bot Don't Understand What The Customer Typed
- Commands List: Code of the commands and it's alias
- Enable Default Replies: Enable Default Replies
- Default Replies: Replies to be Send to The Customer Whenever Matches One of The Requirements.

**Facebook Configuration**

- Enable Messenger Bot: Enable Messenger Bot
- Unavailability Message: Message To Send When Bot Is Disabled. Leave It Empty To Send No Message. Please Check The Maximum Size For Messages On Telegram API, Otherwise Your Message Might Not Be Sent.
- Page Access Token: Your Page Access Token.
- Messenger Support Chat ID: The Chat ID of The Support Admin. This Feature Isn't Very Useful Since You Can Simply Log Into Your Page And Directly Reply Your Customers From There. You Can Forward All Messages to Telegram, Just Write "telegram" in Here to Enable This Feature.
- Messenger Welcome Message: First Message The Bot Will Send To Your Client.
- Messenger Help Message: Message Will be Sent When Customer Asks For Help.
- Messenger About Message: Message Will be Sent When Customer Asks For About.
- Enable Command Listing On Help: Enable Command Listing When Customer Ask For Help
- Enable Command Listing On Error: Enable Command Listing When The Bot Don't Understand What The Customer Typed
- Enable Command Prediction: Enable The Bot to Try to Predict What Command The Customer Wants by Looking into What He Wrote
- Commands List: Code of the commands and it's alias
- Enable Default Replies: Enable Default Replies
- Default Replies: Replies to be Send to The Customer Whenever Matches One of The Requirements.

## Screenshot
General Configuration

![ScreenShot](https://raw.githubusercontent.com/blopa/Magento-Chatbot/master/screenshots/general_configuration.jpg)

wit.ai Configuration

![ScreenShot](https://raw.githubusercontent.com/blopa/Magento-Chatbot/master/screenshots/witai_configuration.jpg)

Telegram Configuration

![ScreenShot](https://raw.githubusercontent.com/blopa/Magento-Chatbot/master/screenshots/telegram_configuration.jpg)

Facebook Messenger Configuration

![ScreenShot](https://raw.githubusercontent.com/blopa/Magento-Chatbot/master/screenshots/facebook_configuration.jpg)

Conversation

![ScreenShot](https://raw.githubusercontent.com/blopa/Magento-Chatbot/master/screenshots/conversation.jpg)

## Release Notes
- **v0.0.13:**
	- Code improvements
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
	- [Backend] Add "starts with", "ends with", "contains", "equals to" and "regex" options to default replies
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
	- [Backend] Add default replies
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
	- Better order searching / listing
	- Add compatibility with configurable products
	- Add compatibility with products with custom options
	- Add better usage of command alias
	- Integrate checkout to Facebook Payment API
	- Use Facebook receipt layout to display orders
	- Add natural language configuration (wit.ai?)
	- Add a custom in-store message chat app
	- Documentation / Tutorials / Videos
	- Save messages on database before sending to make sure it will be sent eventually
	- Save support history
	- Support for audio commands
	- Better uses for wit.ai
	- Better panel UI (like showing some inputs only when that config is selected)
	- Add condition to disable/enable backend inputs
	- Force a command for a customer

## License
Free. Don't forget to star :D and send pull requests. :D

**Free Software, Hell Yeah!**