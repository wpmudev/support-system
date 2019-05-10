# Support System

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations

## Support System takes your service from good to GREAT by adding a support ticket system complete with FAQ.

Quickly set up a full-featured FAQ and reliable ticketing system for your users. Bring the foundations of incredible support to your site or network.  [

![Beautiful front end support form integration.](http://premium.wpmudev.org/wp-content/uploads/2008/09/help-form-735x470.jpg)

![i](http://premium.wpmudev.org/wp-content/uploads/2008/09/help-form-735x470.jpg)

 Beautiful front end support form integration.

### Hassle-free Ticketing System

Users can conveniently submit tickets and track responses from both the front and back end. Support System comes packaged with ticket specific categories for powerful searchability.  Assign staff tickets based on category and make sure questions are answered quickly by the right member of your team.

### Fully Functional Text Editor

Give users the tools they need to clearly articulate their needs. The included text editor is perfect for simple styling and file sharing – ideal for including screenshots.  [

![Embed videos into your posts for easy troubleshooting and sharing tutorials.](http://premium.wpmudev.org/wp-content/uploads/2008/09/embed-video-1470x940.jpg)

![i](http://premium.wpmudev.org/wp-content/uploads/2008/09/embed-video-1470x940.jpg)

 Embed videos into your posts for easy troubleshooting and sharing tutorials.

  Plus, you can embed videos. Use any supported video host, such as YouTube or quickly embed videos from our [Integrated Video Tutorials](http://premium.wpmudev.org/project/unbranded-video-tutorials/) plugin.  [

![Help improve support with FAQ feedback.](http://premium.wpmudev.org/wp-content/uploads/2008/09/faq-feedback-735x470-700x447.jpg)

![i](http://premium.wpmudev.org/wp-content/uploads/2008/09/faq-feedback-735x470.jpg)

 Help improve support with FAQ feedback.

### Answer Questions Before They're Asked

Lighten your workload while providing excellent support with an easy-to-navigate FAQ. Clear your inbox of repetitive questions by answering them before they are even asked.  Make sure users get the answer they need by allowing them to rate how helpful your FAQ posts are. Get feedback and make adjustments for the best available support.

### Build Your Business on Excellent Support

It's easy to lose track of support requests in a crowded email inbox. Support System makes it easy to keep track of conversations with clear threads, staff assignments, email notifications and searchable archives. For maximum convenience and first-class support use Support System.

## Usage

#### Before you start:

_If you are also using our [Multi-DB plugin](https://premium.wpmudev.org/project/multi-db/ "WordPress Multi-DB Plugin - WPMU DEV"), you need to add the global table lines to the db-config.php file in Multi-DB BEFORE installing the Support System plugin or running the sql.txt in Multi-DB_

*   Add these lines to your db-config.php file in Multi-DB:

add_global_table('system_faq'); add_global_table('system_faq_cats'); add_global_table('system_tickets'); add_global_table('system_tickets_cats'); add_global_table('system_tickets_messages');

### To Get Started:

Start by reading [Installing Plugins](https://premium.wpmudev.org/wpmu-manual/installing-regular-plugins-on-wpmu/) section in our comprehensive [WordPress and WordPress Multisite Manual](https://premium.wpmudev.org/manuals/creating-a-network-to-enable-wordpress-multisite/) if you are new to WordPress.

### To Install:

*   On regular WordPress installs - visit **Plugins** and **Activate** the plugin.
*   For WordPress Multisite installs - visit **Network Admin » Plugins** and **Network Activate** the plugin.

#### Settings

Once installed and activated, you will see a new menu item in your admin sidebar: **Support**.

[

![Support System Menu](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2000-menu-700x384.png)

![i](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2000-menu.png)

The menu is the same for both single sites, and multisite installs where it appears in the network admin. The first thing we need to do is configure the settings of the plugin. If you have installed Support System on a multisite, go to **Support > Settings** in your network admin. On a single site install, go to **Support > Settings** in your admin. The settings screen allows you to enter your basic information, set access permissions and privacy level. There is quite a bit going on here, so let's take it in sections starting with the options under the _General_ tab.

##### The general stuff first

![Support System Settings General](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2000-settings-general-1-700x321.png)

 1\. Enter the name you want to use for the Support System on your site.  
2\. Enter the name of the support email sender.  
3\. Enter the email address for all support email.  
4\. Select the default support admin.

 1\. The _Support menu name_ you enter here is what will appear in the admin menu on your site. If you have installed the plugin in a multisite, this will appear in the admin of all sites in the network. 2\. The _Support from name_ appears in the header of all email sent when tickets are submitted. 3\. _Support from e-mail_ is the address the emails come from. 4\. The Main Administrator is the user on your site who is designated as the staff member assigned to handle support tickets.

*   Please note that, in multisite installs, this feature currently only allows for the network admin to be assigned. In the next update, it will be enhanced to allow you to assign a different user.

##### Permissions & Privacy

![Support System Settings General](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2000-settings-general-2-700x634.png)

 1\. Select which user roles can open/see tickets.  
2\. Select the user roles that can see FAQs.  
3\. Set your preferred privacy level.

 1\. Select the _User roles that can open/see tickets_. Note that all available user roles will appear here, including custom ones you create and those created by other plugins. 2\. Select the _User roles that can see the FAQs_. Here again, all roles will appear. If you do not want a FAQ system on your site, _uncheck_ all boxes here. 3\. The _Privacy_ option enables you to set the plugin to allow all users to see all tickets, or allow users to only see their own tickets.

##### Pro Sites integration

This section of the settings will only appear if you have our [Pro Sites](https://premium.wpmudev.org/project/pro-sites/ "Pro Sites") plugin installed on your network. 

![Support System Settings General](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2000-settings-general-3-700x263.png)

 1\. Select the minimum Pro Site level required to access tickets.  
2\. Select the minimum level to access FAQs

 1\. Select the minimum Pro Sites level required to see and submit support _Tickets_ here. 2\. Select the minimum level required to see your _FAQs_ section. Note that if you have disabled the FAQs by unchecking all user roles above, this setting will do nothing at all on your site. Now let's move on the options you'll find under the _Front End_ tab.

##### Front End Options

If you want your users to be able to access your support system via the front end of your site, first check the _Activate Front End_ box. Note that this enables access on the front end of your site _as well as_ the admin of every site (it does not disable the support features in the admin areas of any sites).

![Support System Settings Frontend](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2000-settings-front-1-700x381.png)

You'll then see two new options appear: 

![Support System Settings Frontend](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2000-settings-front-2-700x500.png)

 1\. Check Use Support System styles to use the built-in styles.  
2\. Select the ID of the blog where you want the front-end features.

 1\. Check _Use Support System styles_ if you want to use the built-in styles. Leave this box unchecked if you want use your theme styles, or style the frontend output yourself. 2\. The Blog ID setting enables you to select the ID of the site in your network where you want to activate the frontend features. This can be the main site, or any other site in your network. Once you've set those 2 options, click the _Save Changes_ button at the bottom of your screen to reveal additional options.

![Support System Settings FrontendSupport System Settings Frontend](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-settings-front-700x800.png)

You can now select the page(s) on your chosen site where the support tickets can be viewed and submitted, as well as your FAQ page. You can use the same page for any features if you like, but be sure to copy/paste the required shortcode for each feature.

*   The following shortcode is required to display the support tickets: `[support-system-tickets-index]`
*   This shortcode is required to display the frontend ticket submission form: `[support-system-submit-ticket-form]`
*   This one is required to display the FAQs: `[support-system-faqs]`

The final option under the _Front End_ tab enables you to specify the minimum Pro Site level required to view and submit tickets from the frontend, or view the FAQs. Again, this option will only appear if Pro Sites is installed on your network. Now that we have the settings taken care of, let's go create your support system. This process is identical for both single site and multisite installs.

#### Creating Support & FAQ Categories

If you click right now on the Support or FAQ Manager menu items in the Support menu in a new installation of this plugin, you will see only the page headers with no tickets. That's because there are no tickets to display yet. (If you have updated from an earlier install, your submitted tickets and FAQs should be visible on these pages.) The first thing we want to do is to set up the categories that your users will use to submit their support tickets. Go to Support > Tickets Categories. You will notice there is a Default category already included called General Questions. (This category cannot be deleted and is a great addition to any site as you may have users who may not think their questions fall into any particular category).

![Support System Tickets Categories](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-tickets-categories-700x376.png)

The ticket category screen works just like your post category screen, so it should be quite familiar. Now, before you go crazy in here adding categories willy-nilly, take a minute to think about what categories would be best to include for your users. For example, if you are running a Multisite that provides MarketPress stores, then you might want to include support categories like "Shopping Cart" or "Shipping". Make sure you use broad categories, to make things easier for your users. Too many detailed categories can get confusing. For example, the following are all easily contained within a broader "Adding Products" category:

*   Add Product Description
*   Add Product Image
*   Product Variations

I'll go ahead and add a few categories for my Multisite setup here that offers MarketPress sites to my users.

![Support System Add Ticket Categories](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-tickets-categories-add-700x579.png)

Ok, Our ticket categories are all set up! Now let's do the same thing for our FAQs. Go to Support > FAQ Categories. You will also have a default category here, just like the one on the Tickets Categories page, for General Questions that cannot be deleted. This makes a great place to put miscellaneous information or things that arise with no particular category, or cannot be divided off into a new category. I will go ahead and add categories here that match my Support Categories. That way, I can easily file the most asked ones quickly here when I see something needs to be added.

![Support System Add FAQ Categories](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-faq-categories-add-700x500.png)

#### Adding FAQ Questions

Before we get on to the actual creation of support tickets by your users, let's look at how you can add FAQ questions manually in the FAQ Manager. Go to Support > FAQ Manager, and click on "Add new FAQ" at the top of the screen. You will see that you can manually add new FAQs using the familiar WordPress editor. You'll also see a dropdown menu where you can select from among the FAQ categories you just created.

![Support System FAQ Manager Add New](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-faq-manager-add-700x468.png)

You can go ahead and add a few questions to your own install to test things out. Here's what the FAQ Manager looks like with a couple of questions added. (If you want to delete or edit your test questions, simply hover your mouse pointer hover the title to reveal the links.)

![Support System FAQ Manager Questions](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-faq-manager-questions1-700x363.png)

Notice the "Think is helpful" and "Think is not helpful" column labels? That's where the stats on user votes will appear so you know which of your FAQ items are popular and helpful, and which need improving or could use some fleshing out. In a multisite install, users vote on the FAQs on their own sites. Here's what the FAQ section looks like:

![Support System FAQ Manager Questions Userview](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-faq-manager-questions-userview-700x595.png)

#### How Users Submit Support Questions

Now it's time to see how your users can submit their support questions and view your FAQs. Note that on a multisite install, users can only submit support tickets from sites where they have a role equal to or greater than what you set in your network settings. If you are on a multisite install, go to the wp-admin of any test site you have active in your network where you are an admin (if you don't already have a test site set up, now would be a good time to create one!). If you are on a single site install, log into your wp-admin in another browser (or another computer) as a user with the appropriate role. Once you're in the wp-admin of your test site (or as your test user on a single site), head on down to Support > FAQ. There you will see a view much like the previous image. Click on any of your test FAQs to toggle open the answer. Any time you add new FAQs, they will appear on this screen of every site in your network. Now click the Support > Support menu item. This is what you, and your users, should see:

![Support System Tickets Userview](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-tickets-userview-700x343.png)

Click the "Add new ticket" link at the top of the screen. Here again, you will see the very familiar WordPress post editor that your users will use to submit their support tickets. Easy-peasy. Let's go ahead and create a ticket now.

![Support System Add New Ticket](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-add-new-ticket-700x601.png)

You'll notice that the category can be selected from those you created earlier. Also, as with any support system, users can also set the priority they think their ticket should have. Once you've entered everything for your test ticket, click "Submit new ticket". Your screen will refresh and you'll see your new ticket.

![Support System New Ticket Added](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-new-ticket-added-700x565.png)

#### How it All Ties Together for the Site Admin

That new ticket now appears in the admin under Support. TaDa!

![Support System All Tickets](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-all-tickets-700x418.png)

Of course, on multisite installs, it appears in the network admin under Support. You'll notice that on the network Support screen, there's an additional column telling you the site the ticket was submitted from.

![Support System All Tickets Network](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-all-tickets-network-700x460.png)

And you get a nifty email in your inbox letting you know there was a ticket submitted!

*   This email will only go to the admin email you entered in Support > Settings in the admin dashboard
*   It does not go to all Super admin users on a multisite install.

This email is a ticket notification email only. You can’t reply to the email and must respond directly from the Support Ticket Manager Screen. You will also get emails when tickets are updated and answered! While you're still in the Support area, click the subject title to view all the details of the ticket, and add a reply. Here you can also assign the ticket to a member of your support staff, and change its priority if needed.

![Support System New Ticket Added Network](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-new-ticket-added-network-700x769.png)

You may have noticed a link at the far right of each entry on that screen that says "Create a FAQ". Clicking that link does exactly what it says. It automatically populates the editor under FAQ Manager > Add New Question with the ticket question, answer and category. You can edit all that before clicking the Submit button if you wish.

#### How it All Looks on the Frontend

The page you had set earlier to use as your main Support page will now display all tickets on your site or network.

![Support System Frontend](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-frontend-700x403.png)

Clicking the title of any ticket will open the ticket editor where you can reply to that ticket, and edit any details just like you can in your admin.

![Support System Frontend Edit](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-frontend-edit-700x845.png)

The page you set earlier to use as your FAQ page now displays all your FAQs in a nice accordion style display. You'll also see the voting buttons where your users can vote up the answers they find most helpful.

![Support System Frontend FAQ](https://premium.wpmudev.org/wp-content/uploads/2008/09/support-system-2100-frontend-faq-700x602.png)

Now that you have a good grasp of ticket management in the Support System plugin, you are ready to provide the most AMAZING network support in existence! Good Job! If you run into any snags, just [head on over to the community forums](https://premium.wpmudev.org/forums/tags/support-system#question). Our stellar support team (and members!) are standing by to help you get things up and running smoothly.
