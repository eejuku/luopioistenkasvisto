# CMS Tree Page View – Reorder Pages with a Drag-and-Drop Tree

Contributors: eskapism
Donate link: https://eskapism.se/sida/donate/
Tags: reorder pages, page order, drag-and-drop, custom post types, tree view
Text Domain: cms-tree-page-view
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Reorder, organize and manage your WordPress pages and custom post types in a drag-and-drop tree view — edit, view, add and search in wp-admin.

## Description

**Reorder your WordPress pages by drag and drop, and manage your whole site structure from one visual tree — without ever leaving wp-admin.**

CMS Tree Page View gives WordPress the CMS-like page tree it's always been missing — a structured overview of all your pages and custom post types, much like the view found in a dedicated, page-focused CMS.

See your whole site hierarchy at a glance, then **drag and drop to reorder** your pages, and **edit, view, add and search** them — all without ever leaving the tree. You can even add many pages at once to lay out a brand-new site structure in seconds.

Actively maintained again by its original author, in use since 2010, and translated into more than 20 languages.

**Perfect for** content-heavy sites, agencies and editors who manage large page structures and want a fast, visual way to organize them.

#### Why CMS Tree Page View?

- **See everything in one place.** Stop clicking through paginated page lists — view every page, and exactly how it's nested, in a single tree.
- **Reordering that your theme already understands.** The order you set is saved as WordPress' own `menu_order` — not in a separate hidden table — so navigation menus, `wp_list_pages()` and other plugins can use it directly.
- **Change a post's type just by dragging it.** Drag an item from one tree into another to convert it — turn a page into a custom post type (or back) with no extra plugin. That's rare among reordering plugins.
- **Not just pages.** Enable the tree for posts, WooCommerce products or any public custom post type — hierarchical or not.

#### Features and highlights:

- View your pages & posts in a tree-view, like you view files in Windows Explorer or the Finder on macOS
- Drag and drop to rearrange/order your pages
- Add pages after or inside a page
- Add multiple pages at once - perfect for setting up a new site structure
- Edit pages
- View pages
- Search pages
- Available for both regular pages and custom posts
- Works with both hierarchical and non-hierarchical post types
- View your site hierarchy directly from the WordPress dashboard
- Drag and drop between trees with different post types to change the post type of the dragged item, i.e. change a regular page to become any custom post type
- Support for translation plugin [WPML](http://wordpress.org/extend/plugins/sitepress-multilingual-cms/), so you can manage all the languages of your site

#### Show your pages on your site in the same order as they are in CMS Tree Page View

The plugin stores the order you set as WordPress' "menu order". It does not change how your
theme outputs your pages — to show them on the front end in the tree order, the query that
outputs them must sort by `menu_order`. See the FAQ below for ready-to-use code examples.

#### Screencast

Watch this screencast to see how easy you could be managing your pages:
[youtube http://www.youtube.com/watch?v=H4BGomLi_FU]

#### Translations/Languages

Available in 20+ languages, including German, French, Spanish, Russian, Italian, Dutch, Polish, Swedish, Greek, Finnish and Japanese.

#### Always show your pages in the admin area

If you want to always have a list of your pages available in your WordPress admin area, please check out the plugin
[Admin Menu Tree Page View](http://wordpress.org/extend/plugins/admin-menu-tree-page-view/).

#### Donation and more plugins

- Check out my other plugin [Simple History](https://simple-history.com/?utm_source=cms-tree-page-view&utm_medium=plugin&utm_campaign=cmstpv_readme) if you want to see a log of changes in your WordPress admin. With Simple History you can see login attempts (both failed and sucessful), page changes, plugin updated, and more. It's a great way to view user actions on your site!
- If you like this plugin don't forget to [donate to support further development](https://eskapism.se/sida/donate/).

## Installation

1. Upload the folder "cms-tree-page-view" to "/wp-content/plugins/"
1. Activate the plugin through the "Plugins" menu in WordPress
1. Done!

Now the tree with the pages will be visible both on the dashboard and in the menu under pages.

## Frequently Asked Questions

### I reordered my pages but the new order does not show on my website. Why?

CMS Tree Page View saves the order you set as WordPress' "menu order". It does not change how your theme or other plugins query and display your pages — that is up to the theme. To show your pages in the same order on the front end, the query that outputs them must sort by `menu_order`. For example:

`$query = new WP_Query( array(
	'post_type'      => 'page',
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
	'posts_per_page' => -1,
) );`

For `wp_list_pages()` or a navigation menu, use `'sort_column' => 'menu_order'`. Many themes sort by title or date by default, which is why the tree order may not appear automatically.

### The tree does not load, or keeps showing "Loading..."

This is almost always a JavaScript error from another plugin or your theme that stops the tree from initializing. To track it down:

1. Open your browser's developer console (F12, then the Console tab) on the tree page and look for red errors.
2. Temporarily switch to a default theme such as Twenty Twenty-Four.
3. Deactivate your other plugins one by one to find the conflict.

### Which post types can use the tree?

Any public post type. By default the tree is enabled for hierarchical post types (such as Pages). You can enable or disable it per post type under Settings &rarr; CMS Tree Page View.

### Can I reorder posts, products or other custom post types?

Yes. The tree works with any public post type — pages, regular posts, WooCommerce products, and your own custom post types — as long as you enable it for that post type in the settings. It works with both hierarchical and non-hierarchical post types, and you can even drag an item from one tree into another to change its post type.

### Which pages and posts can each user see in the tree?

The same ones they can see on WordPress' built-in Posts and Pages screens. The tree follows WordPress' own permissions, so each user only sees content they are allowed to manage. For example, a user who can edit only their own content (such as a Contributor) sees their own pages and posts in the tree, not other people's drafts — exactly as on the standard WordPress overview screens.

### Will the tree change or slow down the front end of my site?

No. CMS Tree Page View only adds the tree inside the WordPress admin; it loads nothing on the public side of your site, so it has no effect on front-end performance. The only thing it stores is the page order (as WordPress' "menu order") — see the first FAQ for how to display that order on the front end.

### Does it work with the block editor (Gutenberg)?

Yes. CMS Tree Page View uses WordPress' own edit links, so clicking "Edit" on a page opens it in whatever editor your site uses — the block editor (Gutenberg) or the classic editor. The tree itself works independently of the editor you have active.

### Is the plugin maintained?

Yes. CMS Tree Page View has been taken back over by its original author and is being actively maintained again.

## Screenshots

1. Your entire site structure at a glance — every page, nested as a tree.
2. Edit, view, or add several pages at once — right from the tree.
3. Find any page instantly with built-in search.
4. Drag and drop to reorder pages — the new order is saved automatically.
5. The tree on your dashboard, ready the moment you log in.
6. Switch between the regular list view and the tree view in one click.

## Changelog

### 1.7.1 (June 2026)

#### Security

- The tree and its search now show each user only the posts and pages they are allowed to see, matching WordPress' built-in posts and pages screens. Users who cannot edit other people's content (such as Contributors) no longer see — or find by searching — other authors' drafts in the tree.
- When adding pages, the plugin now respects who is allowed to publish. If a user cannot publish a given post type, their new page is saved as "Pending review" instead of being published.

### 1.7.0 (June 2026)

The plugin is now maintained by its original author again and is being modernized. This release focuses on security and compatibility with current WordPress and PHP.

#### Changed

- Tree rendering no longer runs three database queries per node. Loading a level of the tree now primes the post and meta caches in one query each and resolves all child counts in a single query, instead of one query each for the post, its meta, and a "has children?" check per node. On a tree with ~250 nodes this cut a single level's queries from ~760 to 4 (about 7× faster).
- Tested up to WordPress 7.0.
- Now requires at least WordPress 6.0 and PHP 7.4.

#### Fixed

- The tree now degrades gracefully instead of erroring on PHP 8+ when an unregistered post type is requested.
- The settings screen no longer triggers "setting is unregistered / deprecated" and "headers already sent" notices on modern WordPress. The settings form is now handled directly by the plugin instead of routing through core `options.php`. Settings save exactly as before.
- Removed a redundant database query that ran once per ancestor while expanding search results in the tree.

#### Security

- Fixed a reflected XSS issue where the `post_type` URL parameter was output on the tree pages without escaping.
- Fixed potential stored XSS in the page tree data — page titles, author display names, permalinks, and edit links are now safely JSON-encoded.
- Added a capability check (`manage_options`) to the settings save handler.
- Hardened database queries with prepared statements.
- Added nonce (CSRF) protection to the dismissible notice boxes.
- Added direct file access (`ABSPATH`) guards.

### Older versions

The changelog for all previous releases (1.6.8 and earlier, back to the first release in 2010) is in [changelog.txt](changelog.txt), included with the plugin.

ysaetf7ruhjnm3e2x4tbtletpc35ckeb
