# CMS Tree Page View – Reorder Pages with a Drag-and-Drop Tree

Contributors: eskapism
Donate link: https://eskapism.se/sida/donate/
Tags: reorder pages, page order, drag-and-drop, custom post types, tree view
Text Domain: cms-tree-page-view
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See every page as a drag-and-drop tree. Reorder your site structure in seconds — then edit, add and search right there.

## Description

**Your whole site, laid out as one clear tree. Drag a page to move it, click to edit, and shape your structure in seconds.**

See the tree in action:

https://youtu.be/UV47jXEB-r8

CMS Tree Page View gives WordPress the page overview it's always been missing: one friendly tree of every page and post, so you can finally see how your site fits together — and rearrange it just by dragging.

No more clicking through endless paginated lists. Everything's in front of you: drag a page to reorder or renest it, and edit, add or search right where you are. Setting up a brand-new site? Add a whole batch of pages at once.

Loved by editors, agencies and anyone who looks after a content-heavy site — and actively maintained again by its original author, in use since 2010 and translated into more than 20 languages.

If you also run [Simple History](https://simple-history.com/?utm_source=cms-tree-page-view&utm_medium=plugin&utm_campaign=cmstpv_readme) — my free activity-log plugin — the tree shows recent changes as you go: pick any page to see its history, or glance at the latest edits across your whole site, without leaving the screen.

#### Why you'll like it

- **See your whole site in one place.** Every page, and exactly how it's nested, in a single tree.
- **Rearrange by dragging.** Move a page up, down, or into another page just by dragging it — the order sticks, and your theme can use it too.
- **Do everything without leaving the tree.** Edit, view, add and search your pages right where you see them.
- **Not just pages.** Turn on the tree for posts, products or any content type — and drag an item from one tree into another to change what kind of thing it is.

#### What's in the tree

- A clear, visual tree of your pages and posts — like folders in Finder or File Explorer
- Drag and drop to reorder and renest
- Drag a brand-new page straight into the tree to create it exactly where it belongs
- Add a page after or inside another — or a whole batch at once
- Edit, view and search, all in place
- Full keyboard navigation, so your hands can stay on the keys
- A tree right on your dashboard, ready the moment you log in
- See who moved, edited or added a page, right beside the tree — when the free Simple History plugin is installed
- Works with pages, posts, products and any custom content type — hierarchical or not

#### Showing this order on your site

The plugin stores the order you set as WordPress' "menu order". It does not change how your
theme outputs your pages — to show them on the front end in the tree order, the query that
outputs them must sort by `menu_order`. See the FAQ below for ready-to-use code examples.

#### Translations/Languages

Available in 20+ languages, including German, French, Spanish, Russian, Italian, Dutch, Polish, Swedish, Greek, Finnish and Japanese.

#### Always show your pages in the admin area

If you want to always have a list of your pages available in your WordPress admin area, please check out the plugin
[Admin Menu Tree Page View](https://wordpress.org/plugins/admin-menu-tree-page-view/).

#### Donation and more plugins

- These two plugins work nicely together: install my free [Simple History](https://simple-history.com/?utm_source=cms-tree-page-view&utm_medium=plugin&utm_campaign=cmstpv_readme) plugin and the tree will show you who recently moved, edited or added each page. On its own, Simple History is a complete activity log for your admin — logins (both failed and successful), post and page edits, plugin updates, user changes and more — so you can always see what happened on your site, and when.
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

### Can I see who changed a page, or a history of edits?

Yes — if you also install the free [Simple History](https://wordpress.org/plugins/simple-history/) plugin. When it's active, CMS Tree Page View shows recent activity right in the tree: select any page to see its own history — who edited it, and when — or open the "Latest changes" feed to see edits, moves and new pages across your whole site at a glance. Simple History is a separate free plugin by the same author; the tree works fine without it, and simply hides the history panels when it isn't installed.

### Will the tree change or slow down the front end of my site?

No. CMS Tree Page View only adds the tree inside the WordPress admin; it loads nothing on the public side of your site, so it has no effect on front-end performance. The only thing it stores is the page order (as WordPress' "menu order") — see the first FAQ for how to display that order on the front end.

### Does it work with the block editor (Gutenberg)?

Yes. CMS Tree Page View uses WordPress' own edit links, so clicking "Edit" on a page opens it in whatever editor your site uses — the block editor (Gutenberg) or the classic editor. The tree itself works independently of the editor you have active.

### Is the plugin maintained?

Yes. CMS Tree Page View has been taken back over by its original author and is being actively maintained again.

## Screenshots

1. Your entire site structure at a glance — every page, nested as a tree.
2. Select any page to edit, view, add a child, or reorder it — right from the tree.
3. Add several pages at once — type one title per line, as drafts or published.
4. Find any page instantly with built-in search.
5. Drag and drop to reorder pages — the new order is saved automatically.
6. See who moved, edited or added each page, right beside the tree — when the free Simple History plugin is installed.
7. The tree on your dashboard, ready the moment you log in.
8. Switch between the regular list view and the tree view in one click.

## Changelog

### 2.0.0 (July 2026)

A big, friendly refresh of the whole plugin.

I hope you will like this update.
If you like it: [leave a nice review](https://wordpress.org/support/plugin/cms-tree-page-view/reviews/#new-post) :)
If you hate it: [leave feedback in the support forum](https://wordpress.org/support/plugin/cms-tree-page-view/) so I know what to improve!

#### Added

- Optional Simple History integration — see who changed what, right in the tree.
- Full keyboard navigation — arrow keys to move around, "/" to search, "?" for the shortcut list.
- Select a page to see its details and quick actions (edit, view, add, reorder) beside the tree.
- Drag to create: drop the new-page item anywhere in the tree and the page is created right there — right parent, right position.
- A friendly welcome note after activating the plugin points you straight to your tree.

#### Changed

- Rebuilt the entire tree interface to be faster and easier to use.
- The dashboard widget is now a light, glanceable quick-nav card — click a page to jump straight to its editor, or open the full tree in one click. The editing tools live on the Tree View screen, so your dashboard stays fast and tidy.
- The pages list screen got a clean List / Tree switch at the top — flip between WordPress' regular list and the tree with one click (this replaces the old option that embedded the tree inside the list screen).
- Now requires WordPress 6.6 or later.
- Adding several pages at once is now as simple as typing one title per line and pressing Enter.
- Tightened permission and security checks across the tree.
- The tree sidebar and settings screen now show a small "About this plugin" card with support, review, and Simple History links, replacing the old dismissible promo boxes.
- Made the settings page easier to find: a Plugins-screen "Settings" link, a link in the tree sidebar's About card, and a mention in the welcome notice.

#### Fixed

- Reordering now saves the correct order on sites with a persistent object cache (Redis, Memcached); previously a drag could leave several pages sharing one order value.
- The Tree View menu item no longer overwrites other plugins' admin menu items when a post type's menu lives in a custom location ([report](https://wordpress.org/support/topic/plugin-overwriting-search-and-filter-pro-menu-on-admin/)).

#### Removed

- Removed the bundled WPML / Sitepress integration (I was unable to test and maintain this since I don't use WPML myself).

### 1.7.1 (June 2026)

#### Security

- The tree and its search now show each user only the posts and pages they are allowed to see, matching WordPress' built-in posts and pages screens. Users who cannot edit other people's content (such as Contributors) no longer see — or find by searching — other authors' drafts in the tree.
- When adding pages, the plugin now respects who is allowed to publish. If a user cannot publish a given post type, their new page is saved as "Pending review" instead of being published.

### Older versions

The changelog for all previous releases (1.7.0 and earlier, back to the first release in 2010) is in [changelog.txt](changelog.txt), included with the plugin.

ysaetf7ruhjnm3e2x4tbtletpc35ckeb
