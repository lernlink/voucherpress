=== VoucherPress ===
Contributors: mrwiblog
Donate link: http://www.stillbreathing.co.uk/donate/
Tags: voucher, vouchers, pdf, print, download, offer, code, special, coupon, ticket, token, 
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 0.6

VoucherPress is a Wordpress plugin that allows you to give downloadable, printable vouchers/ticket/coupons/tokens in PDF format away on your site.

== Description ==

Have you ever wanted to give away vouchers, tickets, coupons or tokens on your website? If so this plugin is for you. You can create a voucher with whatever text you want, choosing the layout and font from a range of templates (you can also add your own templates). Vouchers can then be viewed, downloaded and printed from a specified URL.

There are shortcodes to add a link to a particular voucher, or to show an unordered list of all your vouchers.

You can require visitors to provide their name and email address to get a voucher. If an email address is required an email is sent to the address with a link to the voucher URL. Each voucher has a unique code, and vouchers that have an email address associated with them can only be used once, so once a registration-required voucher is downloaded it can't be downloaded again.

The plugin also makes use of the __() function to allow for easy translation.

== Installation ==

The plugin should be placed in your /wp-content/plugins/ directory and activated in the plugin administration screen. The plugin is quite large (over 20mb) as it includes the TCPDF class for creating the PDF file.

== Shortcodes ==

There are three shortcodes available. The first shows a link to a particular voucher, and is in the format:

[voucher id="123"]

The "id" parameter is the unique ID of the voucher. The correct ID to use is available in the screen where you edit the voucher.

The second shows a link to a voucher, but with a preview of the voucher (just the background image, no text) and the voucher name as the image alternate text:

[voucher id="123" preview="true"]

You can also show an unordered list of all your live vouchers using this shortcode:

[voucherlist]

== Frequently Asked Questions ==

= Why did you write this plugin? =

I'm not sure. it seemed like a good idea, and gave me opportunity to learn a little bit about the TCPDF class.

= Does this plugin work with any e-commerce plugins? =

Not at the moment, but I'm sure it could if those e-commerce plugin developers want to get in touch.

== Screenshots ==

1. Creating or editing a voucher
2. Viewing the list of your vouchers, and the mot popular downloaded ones
3. A sample voucher
4. A Microsoft Window print dialog showing the voucher on the paper
5. All the default templates

== Changelog ==

0.6 (2010/03/09) Added shortcode with preview of voucher
0.5.3 (2010/03/01) Fixed bug with upgrades not creating tables
0.5.2 (2010/02/25) Fixed bug when no 404.php page found in template. Added link to voucher to voucher edit page. Clarified some sections of the voucher edit page.
0.5.1 (2010/02/17) Added a support link and donate button
0.5 (2010/02/15) Fixed bug with download counts occurring in older versions of MySQL
0.4 (2010/02/14) Fixed bug with email registration. Changed PDF to force download.
0.3 (2010/02/12) Added check for PHP5
0.2 (2010/02/12) Fixed bugs with SQL
0.1 (2010/02/11) Initial version