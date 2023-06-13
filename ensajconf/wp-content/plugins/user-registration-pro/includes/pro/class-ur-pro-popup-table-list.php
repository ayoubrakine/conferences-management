<?php
/**
 * User Registration Pro Popup Table List
 *
 * @since   1.0.0
 * @package UserRegistrationPro\Popup
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UR_List_Table' ) ) {
	include_once dirname( UR_PLUGIN_FILE ) . '/includes/abstracts/abstract-ur-list-table.php';
}

/**
 * Pro Popup list class.
 */
class User_Registration_Pro_Popup_Table_List extends UR_List_Table {

	/**
	 * Form ID.
	 *
	 * @var int
	 */
	public $popup_type;

	/**
	 * Initialize the popup table list.
	 */
	public function __construct() {

		$page  = ( isset( $_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		$forms = array(
			'all'          => 'All Popups',
			'registration' => 'Registration Popups',
			'login'        => 'Login Popups',
		);

		$latest = key( $forms );

		// @TODO::Verify Nonce
		$this->popup_type = ! empty( $_REQUEST['popup_type'] ) ? $_REQUEST['popup_type'] : $latest;

		$this->post_type       = 'ur_pro_popup';
		$this->page            = 'user-registration-settings&tab=misc&section=popups';
		$this->per_page_option = 'user_registration_pro_popups_per_page';
		parent::__construct(
			array(
				'singular' => 'popup',
				'plural'   => 'popups',
				'ajax'     => false,
				'screen'   => 'user-registration-settings',
			)
		);

		add_filter( 'wp_untrash_post_status', array( $this, 'restore_post_status' ), 10, 3 );
	}

	/**
	 * Since WordPress 5.6 When we restore post item the post_status was changed it to default draft i.e from trash to draf,
	 * But we want it's previous status so using this filter when return previous status.
	 *
	 * @param string $new_status New Status.
	 * @param int    $post_id Post Id.
	 * @param string $previous_status Previous Status.
	 */
	public function restore_post_status( $new_status, $post_id, $previous_status ) {
		return $previous_status;
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		$add_popup_link = esc_url( admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=add-new-popup' ) );
		if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] === 'active' ) {
			esc_html_e( 'Whoops, it appears you do not have any active popups yet.', 'user-registration' );
			echo '<a href="' . $add_popup_link . '"> Add now? </a>';
		} else {
			esc_html_e( 'Whoops, it appears you do not have any popups yet.', 'user-registration' );
			echo '<a href="' . $add_popup_link . '"> Add now? </a>';
		}
	}

	/**
	 * Get Active popups list table columns.
	 *
	 * @return array Columns.
	 */
	public function get_columns() {

		$columns              = array();
		$columns['cb']        = '<input type="checkbox" />';
		$columns['title']     = __( 'Popup', 'user-registration' );
		$columns['type']      = __( 'Popup Type', 'user-registration' );
		$columns['shortcode'] = __( 'Shortcode', 'user-registration' );
		$columns['status']    = __( 'Popup Status', 'user-registration' );
		$columns['author']    = __( 'Author', 'user-registration' );
		$columns['date']      = __( 'Created Date', 'user-registration' );

		return apply_filters( 'user_registration_pro_popup_list_table_columns', $columns );
	}

	/**
	 * Return Pro popup column.
	 *
	 * @param  object $items
	 *
	 * @return array
	 */
	public function get_row_actions( $items ) {
		$edit_link        = $this->get_edit_links( $items );
		$post_type_object = get_post_type_object( 'ur_pro_popup' );
		$post_status      = $items->post_status;

		// Get actions
		$actions = array(
			'id' => sprintf( __( 'ID: %d', 'user-registration' ), $items->ID ),
		);

		if ( current_user_can( $post_type_object->cap->edit_post, $items->ID ) && 'trash' !== $post_status ) {
			$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit', 'user-registration' ) . '</a>';
		}
		if ( current_user_can( $post_type_object->cap->delete_post, $items->ID ) ) {
			if ( 'trash' == $post_status ) {
				$actions['untrash'] = '<a aria-label="' . esc_attr__( 'Restore this item from the Trash', 'user-registration' ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $items->ID ) ), 'untrash-post_' . $items->ID ) . '">' . esc_html__( 'Restore', 'user-registration' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Move this item to the Trash', 'user-registration' ) . '" href="' . get_delete_post_link( $items->ID ) . '">' . esc_html__( 'Trash', 'user-registration' ) . '</a>';
			}
			if ( 'trash' == $post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete this item permanently', 'user-registration' ) . '" href="' . get_delete_post_link( $items->ID, '', true ) . '">' . esc_html__( 'Delete permanently', 'user-registration' ) . '</a>';
			}
		}

		return $actions;
	}

	/**
	 * Post Edit Link.
	 *
	 * @param  object $row
	 *
	 * @return string
	 */
	public function get_edit_links( $row ) {
		return admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=add-new-popup&amp;edit-popup=' . $row->ID );
	}

	/**
	 * Post Duplicate Link.
	 *
	 * @param  object $row
	 *
	 * @return string
	 */
	public function get_duplicate_link( $row ) {
		return admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=add-new-popup&amp;edit-popup=' . $row->ID );
	}

	/**
	 * Return status column.
	 *
	 * @param  object $items
	 *
	 * @return string
	 */
	public function column_status( $items ) {

		if ( isset( $_REQUEST['status'] ) && 'trashed' === $_REQUEST['status'] ) {
			return '<span class="user-registration-badge user-registration-badge--danger-subtle">Trashed</span>';
		} else {
			if ( ! isset( $items->popup_status ) || '' === $items->popup_status ) {
				return '<span class="user-registration-badge user-registration-badge--secondary-subtle">Inactive</span>';
			} else {
				return '<span class="user-registration-badge user-registration-badge--success-subtle">Active</span>';
			}
		}
	}

	/**
	 * Return status popup type.
	 *
	 * @param  object $items
	 *
	 * @return string
	 */
	public function column_type( $items ) {
		return ucfirst( $items->popup_type );
	}

	/**
	 * Return created date column.
	 *
	 * @param  object $items
	 *
	 * @return string
	 */



	function column_shortcode( $items ) {

		$shortcode = '[user_registration_popup id="' . $items->ID . '"]';

		return sprintf( '<span class="shortcode"><input type="text" onfocus="this.select();" readonly="readonly" value=\'%s\' class="large-text code"></span>', $shortcode );

	}



	/**
	 * Prepare table list items.
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items( $args = array() ) {

		$this->prepare_column_headers();
		$per_page     = $this->get_items_per_page( $this->per_page_option );
		$current_page = $this->get_pagenum();

		$this->items         = array();
		$active_items        = array();
		$inactive_items      = array();
		$trashed_items       = array();
		$all_published_items = array();
		$post_status         = array( 'publish', 'trash' );

		if ( isset( $_REQUEST['status'] ) && 'trashed' === $_REQUEST['status'] ) {
			$post_status = array( 'trash' );
		}

		$args = array(
			'post_type'           => 'ur_pro_popup',
			'posts_per_page'      => $per_page,
			'ignore_sticky_posts' => true,
			'paged'               => $current_page,
			'post_status'         => $post_status,
		);

		// Handle the search query.
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field( trim( wp_unslash( $_REQUEST['s'] ) ) ); // WPCS: sanitization ok, CSRF ok.
		}

		$args['orderby'] = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'date_created'; // WPCS: sanitization ok, CSRF ok.
		$args['order']   = isset( $_REQUEST['order'] ) && 'DESC' === strtoupper( $_REQUEST['order'] ) ? 'DESC' : 'ASC';

		$popups = new WP_Query( $args );

		foreach ( $popups->posts as  $item ) {

			$popup_content = json_decode( $item->post_content );

			$popup_content->ID          = $item->ID;
			$popup_content->created_at  = $item->post_date;
			$popup_content->post_author = $item->post_author;
			$popup_content->post_status = $item->post_status;

			$this->items[] = $popup_content;
		}

		foreach ( $this->items as $item ) {

			if ( 'trash' === $item->post_status ) {
				$trashed_items[] = $item;
			} else {

				if ( ! isset( $item->popup_status ) || '' === $item->popup_status ) {
					$inactive_items[] = $item;
				} else {
					$active_items[] = $item;
				}

				// Filter Popups only in all popups page.
				if ( 'all' !== $this->popup_type ) {

					if ( $item->popup_type != $this->popup_type && empty( $_REQUEST['s'] ) ) {
						continue;
					}
				}

				$all_published_items[] = $item;
			}
		}

		// Trashed Items
		if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] === 'trashed' ) {
			$this->items = $trashed_items;
		}
		// Active Items.
		if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] === 'active' ) {
			$this->items = $active_items;
		}
		// Inactive Items.
		if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] === 'inactive' ) {
			$this->items = $inactive_items;
		}

		// Published Items.
		if ( ! isset( $_REQUEST['status'] ) ) {
			$this->items = $all_published_items;
		}

		$this->set_pagination_args(
			array(
				'total_items' => count( $this->items ),
				'per_page'    => $per_page,
				'total_pages' => $popups->max_num_pages,
			)
		);

	}

	/**
	 * Views of the list by status.
	 *
	 * @return string
	 */
	protected function get_views() {
		$status_links   = array();
		$class          = '';
		$trash_count    = 0;
		$active_count   = 0;
		$inactive_count = 0;
		$trash_class    = '';
		$active_class   = '';
		$inactive_class = '';

		if ( isset( $_REQUEST['status'] ) && 'trashed' === $_REQUEST['status'] ) {
			$trash_class = 'current';
		} elseif ( isset( $_REQUEST['status'] ) && 'active' === $_REQUEST['status'] ) {
			$active_class = 'current';
		} elseif ( isset( $_REQUEST['status'] ) && 'active' === $_REQUEST['status'] ) {
			$inactive_class = 'current';
		} else {
			$class = 'current';
		}

		$post_id = array();
		$args    = array(
			'post_type'      => 'ur_pro_popup',
			'post_status'    => array( 'publish', 'trash' ),
			'posts_per_page' => -1,
		);

		$popups = new WP_Query( $args );
		foreach ( $popups->posts as $popup ) {
			$post_id[] = $popup->ID;

			if ( 'publish' === $popup->post_status ) {
				$popup_content = json_decode( $popup->post_content );

				if ( ! isset( $popup_content->popup_status ) || '' === $popup_content->popup_status ) {
					$inactive_count++;
				} else {
					$active_count++;
				}
			} else {
				$trash_count++;
			}
		}
		$total_count = $active_count + $inactive_count;

		/* translators: %s: count */
		$status_links['all']      = "<a href='admin.php?page=user-registration-settings&tab=misc&section=popups' class=" . $class . '>' . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_count, 'codes', 'user-registration' ), number_format_i18n( $total_count ) ) . '</a>';
		$status_links['active']   = "<a href='admin.php?page=user-registration-settings&tab=misc&section=popups&status=active' class=" . $active_class . '>' . sprintf( _nx( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', $active_count, 'codes', 'user-registration' ), number_format_i18n( $active_count ) ) . '</a>';
		$status_links['inactive'] = "<a href='admin.php?page=user-registration-settings&tab=misc&section=popups&status=inactive' class=" . $inactive_class . '>' . sprintf( _nx( 'In Active <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', $inactive_count, 'codes', 'user-registration' ), number_format_i18n( $inactive_count ) ) . '</a>';

		if ( $trash_count > 0 ) {
			$status_links['trashed'] = "<a href='admin.php?page=user-registration-settings&tab=misc&section=popups&status=trashed' class=" . $trash_class . '>' . sprintf( _nx( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', $trash_count, 'codes', 'user-registration' ), number_format_i18n( $trash_count ) ) . '</a>';
		}

		return $status_links;
	}



	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' == $which && isset( $_GET['status'] ) && 'trashed' == $_GET['status'] && current_user_can( 'delete_posts' ) ) {
			echo '<div class="alignleft actions"><a id="delete_all" class="button apply" href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=popups&status=trashed&empty_trash=1' ), 'empty_trash' ) ) . '">' . __( 'Empty trash', 'user-registration' ) . '</a></div>';
		}
	}

	/**
	 * Render the list table page, including header, notices, status filters and table.
	 */
	public function display_page() {
		$this->prepare_items();
		?>

		<div class="wrap">
			<h3 class="ur-settings-section-header main_header">
				<?php esc_html_e( 'User Registration Popups', 'user-registration' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=user-registration-settings&tab=misc&section=add-new-popup' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'user-registration' ); ?></a>
			</h3>
			<hr class="wp-header-end">
			<br class="clear">
			<?php

			$forms = array(
				'all'          => __( 'All Popups', 'user-registration' ),
				'registration' => __( 'Registration Popups', 'user-registration' ),
				'login'        => __( 'Login Popups', 'user-registration' ),
			);

			$latest   = key( $forms );
			$selected = isset( $_REQUEST['popup_type'] ) ? $_REQUEST['popup_type'] : $latest;

			if ( isset( $_POST['popup_type'] ) ) {
				$query_args = add_query_arg(
					array(
						'popup_type' => $selected,
					),
					'//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
				);
				wp_safe_redirect( $query_args );
			}
			?>


			<form id="popups-select" method="get">
				<input type="hidden" name="page" value="user-registration-settings" />
				<input type="hidden" name="tab" value="misc" />
				<input type="hidden" name="section" value="popups" />
				<select id = "form-select" name ="popup_type">
					<?php
					foreach ( $forms as $key => $form ) {
						echo '<option value="' . $key . '" ' . selected( $selected, $key, false ) . '>' . $form . '</option>';
					}
					?>
				</select>
				<button type="submit" class="button" ><?php esc_html_e( 'Filter', 'user-registration' ); ?></button>
			</form>


			<form id="popups-list" method="get">
				<input type="hidden" name="page" value="user-registration-settings" />
				<input type="hidden" name="tab" value="misc" />
				<input type="hidden" name="section" value="popups" />
				<?php
					$this->views();
					$this->search_box( __( 'Search Popups', 'user-registration' ), 'user-registration-pro' );
					$this->display();
				?>
			</form>
		</div>
		<?php
	}
}
