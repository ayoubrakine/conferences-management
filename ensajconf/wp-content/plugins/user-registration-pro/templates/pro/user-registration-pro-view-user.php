<?php
/**
 * User Registration Pro User Detail Layout
 *
 * Shows user lists in grid layout
 *
 * @version 3.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="ur-user-details-container">
	<div class="ur-detail-wrapper">
		<?php
		if ( $show_profile_picture ) {
			?>
				<div class="ur-detail-image">
					<?php
						$gravatar_image      = get_avatar_url( $user_id, $args = null );
						$profile_picture_url = get_user_meta( $user_id, 'user_registration_profile_pic_url', true );
						$image               = ( ! empty( $profile_picture_url ) ) ? $profile_picture_url : $gravatar_image;

					if ( is_numeric( $image ) ) {
						$image = wp_get_attachment_url( $image );
					}
					?>
						<img alt="profile-picture" src="<?php echo esc_url( $image ); ?>">
				</div>
			<?php
		}
		?>
		<div class="ur-info-wrap">
			<h3 class=""><?php echo apply_filters( 'user_registration_pro_view_details_page_title', esc_html__( 'Personal Information', 'user-registration' ) ); ?></h3>
			<table>
				<?php
				foreach ( $user_data_to_show as $key => $value ) {
					?>
						<tr>
							<th>
								<?php
									echo esc_html( $value['label'] );
								?>
									:
							</th>
							<td>
								<?php
								if ( 'wysiwyg' === $value['field_key'] ) {
									$value['value'] = html_entity_decode( $value['value'] );
								}

								echo wp_kses_post( $value['value'] );
								?>
							</td>
						</tr>
					<?php
				}
				?>
			</table>
		</div>
	</div>
</div>
