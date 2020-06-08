# GFeed
A plugin for Woocommerce stores which generates an XML product data feed for use with the Google Merchant Center.

There are many plugins available to generate product data feeds. However, some of these plugins require a payment to access even basic features and some are overly complicated to use. This plugin aims to provide a free, complete and customisable data feed which conforms with Google Merchant Center's product data specification.

1. Installation

To install, navigate to the plugins folder of your wordpress installation (typically wordpress/wp-content/plugins) and create a directory named gfeed. Copy the source files into this new directory.

2. Configuring GFeed

The main GFeed settings page is accessible via the Settings menu of the Wordpress dashboard. Here you can set default values for some product data fields. You can also choose to filter which products will be included in your data feed.

Settings for individual products can be accessed from within the edit product page: Scroll down to the Product Data metabox and click on the tab labelled GFeed. Here you will find many options for setting data fields for the product. You can even map product attributes to supported attributes in the specification.

3. Merchant Center 

To set up the data feed, log in to your Google Merchant Center account. From the dashboard menu, click on products then feeds. Click the add button under 'Primary Feeds'. Select your country of sale and language. Select your destinations. Supply a feed name. Now you will be presented with options for supplying your data feed. Select 'scheduled fetch' and press continue. Enter anything for the file name. In the URL field, enter the gfeed endpoint. The plugin creates an endpoint named 'gfeed-xml', so your URL should be of the form, site_url/gfeed-xml/. Congratulations, you have now added a product data feed.
