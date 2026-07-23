<?php
/**
 * Tree_Data class file.
 *
 * @package cms-tree-page-view
 */

namespace CMS_Tree_Page_View\Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds tree node data (single source of truth for REST + legacy HTML).
 */
class Tree_Data {

	/**
	 * Build node data for one tree level.
	 *
	 * @param array $args { post_type, parent (0=root), view }.
	 * @return list<array<string, mixed>>
	 */
	public static function get_nodes( array $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'post_type' => 'page',
				'parent'    => 0,
				'view'      => 'all',
			)
		);

		$post_type        = (string) $args['post_type'];
		$post_type_object = get_post_type_object( $post_type );
		if ( empty( $post_type_object ) ) {
			return array();
		}

		$parent = (int) $args['parent'];
		$view   = (string) $args['view'];

		$pages = Legacy_Query::get_pages(
			array(
				'post_type' => $post_type,
				'parent'    => $parent,
				'view'      => $view,
			)
		);

		if ( empty( $pages ) ) {
			return array();
		}

		$page_ids = wp_list_pluck( $pages, 'ID' );
		_prime_post_caches( $page_ids, false, true );

		// Flat views (mine/draft/pending/trash) list all matching posts regardless of
		// hierarchy, so they carry no expand arrows — skip the child-count query.
		$is_flat_view = in_array( $view, array( 'mine', 'draft', 'pending', 'trash' ), true );
		$child_counts = $is_flat_view
			? array()
			: Legacy_Query::get_child_counts( $page_ids, $view, $post_type );

		$nodes = array();
		foreach ( $pages as $page_ref ) {
			$post = get_post( $page_ref->ID );
			if ( ! $post ) {
				continue;
			}

			$page_id     = (int) $post->ID;
			$child_count = isset( $child_counts[ $page_id ] ) ? (int) $child_counts[ $page_id ] : 0;

			$nodes[] = self::build_node( $post, $child_count, $post_type_object );
		}

		return $nodes;
	}

	/**
	 * Build the node lists for one or more parent levels at once — the payload a
	 * mutation (move / add) returns so the client can apply the new tree state
	 * optimistically without a separate read round trip. Each entry is the same
	 * node shape get_nodes() produces.
	 *
	 * @param int[]  $parent_ids Parent post IDs (0 = root); de-duplicated, order kept.
	 * @param string $post_type  Post type the requesting tree is showing.
	 * @param string $view       Active view (all|publish|mine|draft|pending|trash).
	 * @return list<array{parent: int, nodes: list<array<string, mixed>>}>
	 */
	public static function get_levels( array $parent_ids, string $post_type, string $view ): array {
		$seen   = array();
		$levels = array();
		foreach ( $parent_ids as $parent_id ) {
			$parent_id = (int) $parent_id;
			if ( isset( $seen[ $parent_id ] ) ) {
				continue;
			}
			$seen[ $parent_id ] = true;
			$levels[]           = array(
				'parent' => $parent_id,
				'nodes'  => self::get_nodes(
					array(
						'post_type' => $post_type,
						'parent'    => $parent_id,
						'view'      => $view,
					)
				),
			);
		}
		return $levels;
	}

	/**
	 * Assemble the shared node array for one post.
	 *
	 * Single source of truth for both get_nodes() (tree levels) and get_detail()
	 * (the selected-page card). The per-post-type capability object is passed in
	 * so get_nodes() can reuse the level's object and get_detail() can derive it
	 * from the post.
	 *
	 * @param \WP_Post      $post             Post.
	 * @param int           $child_count      Number of visible children.
	 * @param \WP_Post_Type $post_type_object Post type object for cap checks.
	 * @return array<string, mixed>
	 */
	private static function build_node( \WP_Post $post, int $child_count, \WP_Post_Type $post_type_object ): array {
		$page_id = (int) $post->ID;

		$title = self::node_title( $post );

		$modified_ts = strtotime( $post->post_modified );

		return array(
			'id'                  => $page_id,
			'title'               => $title,
			'status'              => $post->post_status,
			'statusLabel'         => self::status_label( $post->post_status ),
			'postType'            => $post->post_type,
			'hasChildren'         => $child_count > 0,
			'childCount'          => $child_count,
			'menuOrder'           => (int) $post->menu_order,
			'editUrl'             => (string) get_edit_post_link( $page_id, 'raw' ),
			'viewUrl'             => (string) get_permalink( $page_id ),
			'permalink'           => (string) get_permalink( $page_id ),
			'modified'            => array(
				'raw'       => $post->post_modified,
				'formatted' => date_i18n( get_option( 'date_format' ), $modified_ts ),
			),
			'author'              => self::modified_author( $post ),
			'isPasswordProtected' => '' !== $post->post_password,
			'canEdit'             => (bool) apply_filters(
				'cms_tree_page_view_post_can_edit',
				current_user_can( $post_type_object->cap->edit_post, $page_id ),
				$page_id
			),
			'canAddInside'        => Legacy_Query::is_post_type_hierarchical( $post_type_object ) && (bool) apply_filters(
				'cms_tree_page_view_post_user_can_add_inside',
				current_user_can( $post_type_object->cap->create_posts, $page_id ),
				$page_id
			),
			'canAddAfter'         => (bool) apply_filters(
				'cms_tree_page_view_post_user_can_add_after',
				current_user_can( $post_type_object->cap->create_posts, $page_id ),
				$page_id
			),
		);
	}

	/**
	 * A post's display title, prepared the one way the tree renders titles:
	 * get_the_title() + the plugin's title filter, entity-decoded so React
	 * escapes it exactly once (no double-encoding), with the legacy untitled
	 * fallback. Shared by build_node() and context_node().
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private static function node_title( \WP_Post $post ): string {
		$title = get_the_title( $post->ID );
		$title = apply_filters( 'cms_tree_page_view_post_title', $title, $post );
		$title = html_entity_decode( $title, ENT_QUOTES, get_bloginfo( 'charset' ) );
		if ( empty( $title ) ) {
			$title = __( '<Untitled page>', 'cms-tree-page-view' );
		}
		return $title;
	}

	/**
	 * Minimal node for an ancestor shown only as greyed positional context under a
	 * status/search filter. Deliberately NOT build_node(): a match can sit under a
	 * parent the current user cannot read (another author's private/draft page, or
	 * one of a different status), so this carries only what the tree needs to
	 * render and seed a context row — id, title, post type, and folder/caret state.
	 * It never exposes the parent's author, modified date, permalink, status,
	 * password-protected flag, or edit/view links, which a full node would. Mirrors
	 * get_detail()'s deliberately minimal breadcrumb entries.
	 *
	 * A child_count of 1 forces hasChildren=true — an ancestor on a match's path
	 * is a folder by definition; its real count is filled in on the next real
	 * fetch of that level.
	 *
	 * @param \WP_Post $post Ancestor post.
	 * @return array<string, mixed>
	 */
	private static function context_node( \WP_Post $post ): array {
		return array(
			'id'          => (int) $post->ID,
			'title'       => self::node_title( $post ),
			'postType'    => $post->post_type,
			'hasChildren' => true,
			'childCount'  => 1,
			// Ordering only (not sensitive) — the depth-first tree-order sort keys
			// matches on their ancestors' (menuOrder, title). See tree_order_sort_key().
			'menuOrder'   => (int) $post->menu_order,
		);
	}

	/**
	 * Build the detail-card data for one page: the shared node plus breadcrumb,
	 * excerpt, native preview URL, template label, and the delete capability.
	 *
	 * @param int $id Post ID.
	 * @return array<string, mixed>|null Null when the post does not exist.
	 */
	public static function get_detail( int $id ): ?array {
		$id   = (int) $id;
		$post = $id ? get_post( $id ) : null;
		if ( ! $post ) {
			return null;
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( empty( $post_type_object ) ) {
			return null;
		}

		$counts      = Legacy_Query::get_child_counts( array( $id ), 'all', $post->post_type );
		$child_count = isset( $counts[ $id ] ) ? (int) $counts[ $id ] : 0;

		$node = self::build_node( $post, $child_count, $post_type_object );

		// Breadcrumb: get_post_ancestors() returns immediate-parent-first; reverse
		// to root-first for left-to-right display.
		$ancestors = array();
		foreach ( array_reverse( get_post_ancestors( $post ) ) as $anc_id ) {
			// An ancestor id can outlive its post (e.g. hard-deleted outside the
			// trash flow, orphaning its children's post_parent) — skip rather than
			// pass a null WP_Post into the post_title filter.
			$anc_post = get_post( $anc_id );
			if ( ! $anc_post ) {
				continue;
			}
			$ancestors[] = array(
				'id'      => (int) $anc_id,
				'title'   => (string) apply_filters( 'cms_tree_page_view_post_title', get_the_title( $anc_id ), $anc_post ),
				'editUrl' => (string) get_edit_post_link( $anc_id, 'raw' ),
			);
		}

		// Template label.
		$template_slug = get_page_template_slug( $post );
		if ( $template_slug ) {
			$templates = wp_get_theme()->get_page_templates( $post );
			$template  = isset( $templates[ $template_slug ] ) ? $templates[ $template_slug ] : $template_slug;
		} else {
			$template = __( 'Default', 'cms-tree-page-view' );
		}

		// Gate the nonce-bearing preview URL and the draft excerpt to users who
		// can edit this specific post — reuse build_node()'s own 'canEdit' (which
		// already applies the cms_tree_page_view_post_can_edit filter) rather than
		// a separate raw current_user_can() check, so a site hooking that filter
		// to override per-post editability doesn't see this exposure disagree
		// with the canEdit badge shown for the same post.
		$can_edit_this = $node['canEdit'];

		$node['ancestors']  = $ancestors;
		$node['excerpt']    = $can_edit_this ? (string) get_the_excerpt( $post ) : '';
		$node['previewUrl'] = $can_edit_this
			? (string) add_query_arg( 'cms_tpv_preview', '1', get_preview_post_link( $post ) )
			: '';
		$node['template']   = (string) $template;
		$node['canDelete']  = (bool) current_user_can( $post_type_object->cap->delete_post, $id );

		return $node;
	}

	/**
	 * Largest number of matches a status filter returns. Unlike search (capped
	 * at 100), a status filter must return EVERY matching page: the count on its
	 * tab comes from wp_count_posts(), so truncating here would reintroduce the
	 * very "tab says N, tree shows fewer" mismatch the tree+context view fixes.
	 * -1 = unbounded, matching cms_tpv_get_pages()'s own full listing; the plugin
	 * targets page trees in the hundreds, so this is intentionally not paginated.
	 */
	const FILTER_RESULT_LIMIT = -1;

	/**
	 * Flat list of nodes whose title matches a search term, scoped to the
	 * post type and the current user's read permission.
	 *
	 * Mirrors the author restriction in cms_tpv_get_pages(): when the user cannot
	 * edit others' posts of this type, the query is limited to their own posts so
	 * a low-privileged user cannot enumerate other authors' drafts via search.
	 *
	 * @param string $post_type Post type.
	 * @param string $term      Raw search term.
	 * @return list<array<string,mixed>> Flat node arrays, each carrying an
	 *                                   `ancestors` key (root-first list of full
	 *                                   ancestor node objects, empty for
	 *                                   root-level matches); empty when term is blank.
	 */
	public static function search_nodes( string $post_type, string $term ): array {
		$term = trim( $term );
		if ( '' === $term ) {
			return array();
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( empty( $post_type_object ) ) {
			return array();
		}

		$query_args = array(
			'post_type'      => $post_type,
			's'              => $term,
			// Match the page title only — the tree shows titles, so a content-only
			// match would paint a row yellow with nothing visibly highlighted.
			'search_columns' => array( 'post_title' ),
			'post_status'    => self::searchable_statuses(),
			'fields'         => 'ids',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		// Author restriction — identical rule to cms_tpv_get_pages().
		if ( ! current_user_can( $post_type_object->cap->edit_others_posts ) ) {
			$query_args['author'] = get_current_user_id();
		}

		$ids = ( new \WP_Query( $query_args ) )->posts;
		return self::matches_with_ancestors( $ids, $post_type_object );
	}

	/**
	 * Flat list of nodes matching a status-filter view (mine/publish/draft/
	 * pending/trash), each carrying its full ancestor chain — the same shape
	 * search_nodes() returns, so the client renders both the same way: a tree of
	 * matches with their ancestors shown as muted context.
	 *
	 * This is the unified answer to "filter the tree by a per-post attribute":
	 * a status filter is just a search whose predicate is the status (or author,
	 * for `mine`) instead of a text term. Returning ancestors means a match whose
	 * parent doesn't match the filter (a published child under a draft parent) is
	 * still reachable under a context ancestor, instead of silently vanishing.
	 *
	 * @param string $post_type Post type.
	 * @param string $view      Status view: mine|publish|draft|pending|trash.
	 *                          Anything else (incl. 'all') returns [] — the base
	 *                          hierarchical tree handles the unfiltered view.
	 * @return list<array<string,mixed>> Match nodes with `ancestors`, tree-ordered.
	 */
	public static function filter_nodes( string $post_type, string $view ): array {
		$post_type_object = get_post_type_object( $post_type );
		if ( empty( $post_type_object ) ) {
			return array();
		}

		$query_args = array(
			'post_type'      => $post_type,
			'fields'         => 'ids',
			'posts_per_page' => self::FILTER_RESULT_LIMIT,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		);

		switch ( $view ) {
			case 'mine':
				// Every non-trash status, but only the current user's own posts.
				$query_args['post_status'] = self::searchable_statuses();
				$query_args['author']      = get_current_user_id();
				break;
			case 'publish':
			case 'draft':
			case 'pending':
				$query_args['post_status'] = $view;
				break;
			case 'trash':
				$query_args['post_status'] = 'trash';
				break;
			default:
				return array();
		}

		// Author restriction — identical rule to cms_tpv_get_pages() / search_nodes
		// ('mine' already scopes to the current user above).
		if ( 'mine' !== $view && ! current_user_can( $post_type_object->cap->edit_others_posts ) ) {
			$query_args['author'] = get_current_user_id();
		}

		$ids = ( new \WP_Query( $query_args ) )->posts;
		return self::matches_with_ancestors( $ids, $post_type_object );
	}

	/**
	 * Turn a flat list of matched post IDs into match nodes carrying their full
	 * root-first ancestor chains, sorted into depth-first tree order. Shared by
	 * search_nodes() and filter_nodes().
	 *
	 * @param int[]         $ids              Matched post IDs.
	 * @param \WP_Post_Type $post_type_object Post type object for the matches.
	 * @return list<array<string,mixed>>
	 */
	private static function matches_with_ancestors( array $ids, \WP_Post_Type $post_type_object ): array {
		if ( empty( $ids ) ) {
			return array();
		}

		_prime_post_caches( $ids, false, true );

		// Collect every match's ancestor chain up front (root-first) and prime all
		// the ancestor caches in ONE query, instead of get_post()'ing each ancestor
		// inside the per-match loop — an N+1 across the whole result set, which for
		// this deliberately-unbounded filter (see FILTER_RESULT_LIMIT) could be a
		// lot of queries. get_post_ancestors() reads from the already-primed match
		// caches.
		$chains       = array();
		$ancestor_ids = array();
		foreach ( $ids as $id ) {
			$chain         = array_reverse( get_post_ancestors( $id ) );
			$chains[ $id ] = $chain;
			foreach ( $chain as $anc_id ) {
				$ancestor_ids[ (int) $anc_id ] = true;
			}
		}
		if ( ! empty( $ancestor_ids ) ) {
			_prime_post_caches( array_keys( $ancestor_ids ), false, true );
		}

		$nodes = array();
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( ! $post ) {
				continue;
			}
			// Matches are seeded flat: childCount 0 here, because their real
			// childCount/hasChildren are populated normally once they're rendered
			// as part of the tree (after auto-expand reveals the path to them).
			$node = self::build_node( $post, 0, $post_type_object );

			// Ancestor context nodes (root-first) so the client can seed its cache
			// and auto-expand the path to this match without a network round trip
			// per level. These are context_node()s (id/title/postType/folder/order),
			// NOT full nodes — a match's parent may be one the user can't read, so we
			// don't disclose its author/status/permalink/etc. (see context_node()).
			$ancestor_nodes = array();
			foreach ( $chains[ $id ] as $anc_id ) {
				$anc_post = get_post( $anc_id );
				if ( ! $anc_post ) {
					continue;
				}
				$ancestor_nodes[] = self::context_node( $anc_post );
			}
			$node['ancestors'] = $ancestor_nodes;

			$nodes[] = $node;
		}

		// The raw query results are title-sorted across the whole post type, with
		// no concept of tree position — so jumping Prev/Next between matches would
		// skip around the tree instead of visiting them top to bottom. Re-sort
		// depth-first using each match's already-fetched ancestor chain: two
		// matches are compared level by level (root-first) by (menu_order, title),
		// the same tie-break cms_tpv_get_pages() uses for sibling order, so this
		// reproduces the order a user would hit scrolling the tree top to bottom.
		//
		// Schwartzian transform: precompute each node's sort key once instead
		// of rebuilding it from the ancestors array on every comparison
		// usort() makes (O(n log n) rebuilds of the same key otherwise).
		$keyed = array_map(
			static function ( array $node ): array {
				return array( self::tree_order_sort_key( $node ), $node );
			},
			$nodes
		);
		usort( $keyed, array( __CLASS__, 'compare_by_tree_order' ) );

		return array_column( $keyed, 1 );
	}

	/**
	 * Usort() comparator: depth-first tree order for two search_nodes() results,
	 * given each node's precomputed sort key (see search_nodes()'s Schwartzian
	 * transform) rather than its raw node array.
	 *
	 * @param array{0: list<array{0: int, 1: string}>, 1: array<string, mixed>} $a First [key, node] pair.
	 * @param array{0: list<array{0: int, 1: string}>, 1: array<string, mixed>} $b Second [key, node] pair.
	 * @return int Negative if $a sorts first, positive if $b does, 0 if equal.
	 */
	private static function compare_by_tree_order( array $a, array $b ): int {
		$key_a = $a[0];
		$key_b = $b[0];
		$depth = min( count( $key_a ), count( $key_b ) );

		for ( $i = 0; $i < $depth; $i++ ) {
			if ( $key_a[ $i ][0] !== $key_b[ $i ][0] ) {
				return $key_a[ $i ][0] <=> $key_b[ $i ][0];
			}
			$cmp = strnatcasecmp( $key_a[ $i ][1], $key_b[ $i ][1] );
			if ( 0 !== $cmp ) {
				return $cmp;
			}
		}

		return count( $key_a ) <=> count( $key_b );
	}

	/**
	 * Build a search_nodes() result's depth-first sort key: one `[menu_order,
	 * title]` pair per level from the root ancestor down to the match itself.
	 *
	 * @param array<string, mixed> $node A search_nodes() result (carries `ancestors`).
	 * @return list<array{0: int, 1: string}>
	 */
	private static function tree_order_sort_key( array $node ): array {
		$key = array();
		foreach ( $node['ancestors'] as $ancestor ) {
			$key[] = array( $ancestor['menuOrder'], $ancestor['title'] );
		}
		$key[] = array( $node['menuOrder'], $node['title'] );
		return $key;
	}

	/**
	 * Per-status counts for the status-filter tabs, scoped to what the current
	 * user may read (wp_count_posts '...readable' applies the author restriction).
	 *
	 * @param string $post_type Post type.
	 * @return array<string,int> { all, mine, publish, draft, pending, trash }
	 */
	public static function get_status_counts( string $post_type ): array {
		$counts = wp_count_posts( $post_type, 'readable' );

		$publish = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$draft   = isset( $counts->draft ) ? (int) $counts->draft : 0;
		$pending = isset( $counts->pending ) ? (int) $counts->pending : 0;
		$trash   = isset( $counts->trash ) ? (int) $counts->trash : 0;

		// "all" = every status the tree's default 'any' listing shows: all
		// registered statuses except trash and auto-draft.
		$all = 0;
		foreach ( (array) $counts as $status => $n ) {
			if ( 'trash' === $status || 'auto-draft' === $status ) {
				continue;
			}
			$all += (int) $n;
		}

		// "mine" = the current user's posts of this type in any non-trash status.
		$mine_q = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'author'         => get_current_user_id(),
				'post_status'    => self::searchable_statuses(),
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => false,
			)
		);
		$mine   = (int) $mine_q->found_posts;

		return array(
			'all'     => $all,
			'mine'    => $mine,
			'publish' => $publish,
			'draft'   => $draft,
			'pending' => $pending,
			'trash'   => $trash,
		);
	}

	/**
	 * Post statuses shown in the tree's non-trash listings — the admin-listed
	 * statuses (so custom registered statuses are included) minus auto-draft/trash.
	 *
	 * @return string[]
	 */
	private static function searchable_statuses(): array {
		$statuses = get_post_stati( array( 'show_in_admin_status_list' => true ) );
		unset( $statuses['trash'], $statuses['auto-draft'] );
		$statuses = array_values( $statuses );
		return $statuses ? $statuses : array( 'publish', 'draft', 'pending', 'private', 'future' );
	}

	/**
	 * Human label for a post status.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	private static function status_label( $status ) {
		$obj = get_post_status_object( $status );
		return $obj ? $obj->label : ucfirst( $status );
	}

	/**
	 * Display name of the user who last modified the post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	private static function modified_author( $post ) {
		$last_id = get_post_meta( $post->ID, '_edit_last', true );
		$user_id = $last_id ? (int) $last_id : (int) $post->post_author;
		$user    = get_userdata( $user_id );
		// Empty (not a literal "Unknown user") when there's no resolvable user —
		// e.g. imported/seeded/programmatically-created pages, where post_author
		// is 0 and no _edit_last exists. The card omits the "By …" segment
		// entirely rather than showing a placeholder that reads like a data error.
		return $user ? $user->display_name : '';
	}
}
