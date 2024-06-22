Harvest Support Chat Plugin

Description
The Harvest Support Chat is a WordPress plugin that enhances the support chat feature by adding the user's email, name, cart contents, and shipping state to the Livechat support chat iframe. This information aids support agents in providing personalized assistance. The Livechat window can be added to any page using the shortcode [ab_support_chat].

How it Works
The plugin works by fetching the current user's data, including their email, name, role, shipping state, and cart contents. This information is then added to the Livechat support chat iframe. and can be seen by support agents in the Livechat dashboard. **Note, email is not passed in a usual way, if we pass email to Livechat they will contact the user with a Livechat branded email address verification email. This is their policy and they will not remove it.  

Here is a brief overview of the main function ab_support_chat_func():

- Fetches the current user's data and the current page URL.
- Checks if the user is logged in.
- If the user is logged in, it fetches the user's email, name, role, shipping state, and cart contents.
- Creates an iframe with the user's information and returns it.

Usage
Shortcode [ab_support_chat] added to a Jet Popup slideout menu visible on all funnel pages & shop.

Livechat Guide:
https://heroguides.livechat.com/docs/LiveChat_Guides/Iframe_embed_and_custom_chat_page
