=== 2Performant Product Importer ===
Contributors: 2parale, tetele
Donate link: 
Tags: affiliate, posts, commerce, products
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 0.9.8

Imports products from product feeds in 2Performant affiliate networks, such as 2Parale.ro.

== Description ==

Use this plugin to insert product data in your posts' content or custom fields.

It requires authentication as an affiliate in one of these networks. Products are imported as individual posts (or other custom post types, configurable) which can use several custom fields based on product info from the feeds.

Note that you have to embed this information in your theme manually using the `tp_get_the_product_field( $field )` or `tp_the_product_field( $field )` methods. `$field` can be any string defined in the *Product fields* table on the settings page.

== Installation ==

1. Unzip and upload `2performant-product-importer` to the `/wp-content/plugins/` directory of your site
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin's settings
1. Place `<?php tp_the_product_info('info_field'); ?>` in your templates

== Frequently Asked Questions ==

= What do I do after installing and setting up the plugin? =
You can add products using one of these metehods:

1. *Posts (or other custom post type you selected)* > *Add from feed* - Select a product and *Add* it, choosing the category it should reside in.

1. *Posts/Pages* > *Add new* - Use the *Insert 2Performant Product* button on the visual editor (the last button on the first row), choose a product and click *Insert*

= I have installed the plugin, added a product from a feed, but it doesn't show up on my website. Why? =
You have to manually display the product fields using the `tp_the_product_field( $key )` method.

= Whoa! That product snippet looks totally awkward on my blog. You call that looking good? =
Actually, no. But there's a reason for it: you can customize the output depending on your theme and imagination. Just go to the settings page and edit the output template.

= I have a long list of imported products and for some reason when I update them all, the script never finishes. What should I do? =
There's a bug/feature in Wordpress which gets in the way of the process. It's documented [here](http://core.trac.wordpress.org/ticket/11831). The quickfix is to add `define('ALTERNATE_WP_CRON', true);` to `wp-config.php`.

== Changelog ==

= 0.9.7 =
* **Critical Bugfix** accounting for API change
* **New feature** `tp_get_the_product_field()` now accepts post ID as parameter

= 0.9.5 =
* **Critical Bugfix** AJAX now works on environments which cannot use __callStatic (i.e. PHP <5.3)
* **Bugfix** Product mass update now divided into batches of configurable size. This way, even when updating large lists of products, script will not fail.

= 0.9.4 =
* **Bugfix** Now shows failed updated in update log and displays summary when some products fail update.

= 0.9.3 =
* **New Feature** Customizable product template - change the way the inserted product shows up on your website
* **Bugfix** Several minor bugfixes and security issues handled

= 0.9.2 =
* **Hotfix** Resolves incompatibility with environments running PHP <5.3
* **Bugfix** Insert product into post box not resizing correctly

= 0.9.1 =
* **New Feature** Introduced ability to insert product info dirrectly into post content using the WYSIWYG editor (see *Insert 2Performant Product* button) and shortcode
* **Bugfix** Product update for drafts/pending products
* **Bugfix** Campaigns now sorted by name
* **Bugfix** Does not delete regular posts on update

== Upgrade Notice ==

= 0.9.7 =
Hotfix accounting for API change. You HAVE to install this version if you used 0.9.6.

= 0.9.5 =
Hotfix for updating products in environments running PHP <5.3

= 0.9.2 =
Hotfix for environments running PHP <5.3

= 0.9.1 =
Fixes a fatal error shown on environments without required PEAR classes. Introduces ability to insert product into post.

= 0.9 =
This version is the first version