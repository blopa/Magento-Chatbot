# Magento Chatbot Module (Telegram, Messenger, Whatsapp and WeChat)

Source code for the Magento Chatbot (Telegram, Messenger, Whatsapp and WeChat), with this module you can fully integrate you Magento store with the most popular chat apps in the market.

**For now only Telegram and Facebook Messenger is implemented, try it out Telegram at [@MyChatbotStoreBot](https://telegram.me/MyChatbotStoreBot).**

A big thanks to [Eleirbag89](https://github.com/Eleirbag89/) who wrote [this](https://github.com/Eleirbag89/TelegramBotPHP) simple PHP wrapper for Telegram.


### Installation

1. Unpack the extension ZIP file in your Magento root directory
2. Clear the Magento cache: **System > Cache Management**
3. Log out the Magento admin and log back in to clear the ACL list
4. Recompile if you are using the Magento Compiler

### Usage

Go to **System > General Settings  > Chatbot Settings**

**General Configuration**

- Your Secret Key: This is Your Custom Secret Key Used to Activate/Deactivate The API Webhook

**Telegram Configuration**

- Enable Telegram Bot: Enable Telegram Bot
- Telegram Bot API Key: Your Telegram Bot API Key.
- Telegram Support Group ID: ID of Group that the support messages will be forwarded. e.g. g123456789
- Telegram Welcome Message: First Message The Bot Will Send To Your Client.
- Telegram Help Message: Message Will be Sent When Customer Asks For Help.
- Telegram About Message: Message Will be Sent When Customer Asks For About.
- Enable Command Listing: Enable Command Listing When Customer Ask For About
- Enabled Commands: List of Enabled Commands
- Commands List: Code of the commands

**Facebook Configuration**

- Enable Messenger Bot: Enable Messenger Bot
- Messenger Bot API Key: Your Messenger Bot API Key.
- Messenger Support Group ID: Reserved for future use, for now Facebook dosen't allow bots on group chats
- Messenger Welcome Message: First Message The Bot Will Send To Your Client.
- Messenger Help Message: Message Will be Sent When Customer Asks For Help.
- Messenger About Message: Message Will be Sent When Customer Asks For About.
- Enable Command Listing: Enable Command Listing When Customer Ask For About
- Enable Command Prediction: Enable The Bot to Try to Predict What Command The Customer Wants by Looking into What He Wrote
- Enabled Commands: List of Enabled Commands
- Commands List: Code of the commands and alias

# F.A.Q.
**Q: When the other chatbots integrations are going to be ready?**

A: I'm not sure, I work in my free hours. I'm trying to finish it ASAP. Pull requests are very welcome.

**Q: Can you implement <???> function/bot integration?**

A: I can try. Open a issue and I'll see what I can do.

**Q: Your bot is awesome. How can I help?**

A: Thank you! You can help by codding more features, creating pull requests, or donating using Bitcoin: **1BdL9w4SscX21b2qeiP1ApAFNAYhPj5GgG**

License
----
Free. Don't forget to star :D and send pull requests. :D

**Free Software, Hell Yeah!**