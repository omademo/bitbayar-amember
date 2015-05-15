# Description
Amember Bitcoin Payment plugin using BitBayar service. (https://bitbayar.com)

# Precondition
You must have a BitBayar merchant account to use this plugin. It's free and easy to [register](https://bitbayar.com/register).

# Installation
Upload this files into the Amember directory on your webserver (parent directory of application/default/plugins/payment).

# Configuration
1. Get **API Token** in your BitBayar merchant account:
  * Log into [https://bitbayar.com](https://bitbayar.com) with your account username/password.
  * Go to menu [Setting & API](https://bitbayar.com/setting)
  * On the first box column, you will find **API TOKEN**
  * Select and copy the entire string for your API Token. It will look something like this: GD1FA2CC2A3357FDF45B84744FFF7102.
3. In the admin control panel, go to **Configuration > Setup/Configuration > Plugins**, select **BitBayar** in the Payment Plugins and click Save.
4. Select BitBayar tab on top menu and Paste the **API Token** string that you copied from step 1.
5. Click **Save Changes**.

You're done!


# Usage
When a client chooses the BitBayar payment method, they will redirected to BitBayar payment page. Once payment is received, a link is presented to the client that will return them to thanks page.

In your Amember control panel, you can see the information associated with each order made via BitBayar by choosing **Reports > Payments**. This screen will tell you whether payment has been received by the BitBayar servers. You can also view the details for any paid invoice inside your BitBayar merchant dashboard under the **Payments** page.


# Support
*Bitbayar Support*
* [Support](https://bitbayar.com/support)
* [BitBayar Documentation](https://bitbayar.com/dev)

*Amember Support:*
* [Homepage](http://www.amember.com/)
* [Documentation](http://www.amember.com/docs/Main_Page)
* [SupportForums](http://www.amember.com/forum/)


#License
http://opensource.org/licenses/MIT

#Author
## Teddy Fresnel
Website : [www.teddyfresnel.com](http://www.teddyfresnel.com)
