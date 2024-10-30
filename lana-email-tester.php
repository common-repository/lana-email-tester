<?php
/**
 * Plugin Name: Lana Email Tester
 * Plugin URI: https://lana.codes/product/lana-email-tester/
 * Description: Send test email.
 * Version: 1.1.0
 * Author: Lana Codes
 * Author URI: https://lana.codes/
 * Text Domain: lana-email-tester
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or die();
define( 'LANA_EMAIL_TESTER_VERSION', '1.1.0' );
define( 'LANA_EMAIL_TESTER_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'LANA_EMAIL_TESTER_DIR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Language
 * load
 */
load_plugin_textdomain( 'lana-email-tester', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

/**
 * Add plugin action links
 *
 * @param $links
 *
 * @return mixed
 */
function lana_email_tester_add_plugin_action_links( $links ) {

	$tester_url = esc_url( admin_url( 'tools.php?page=lana-email-tester.php' ) );

	/** add tester link */
	$tester_link = sprintf( '<a href="%s">%s</a>', $tester_url, __( 'Tester', 'lana-email-tester' ) );
	array_unshift( $links, $tester_link );

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'lana_email_tester_add_plugin_action_links' );

/**
 * Lana Email Tester
 * add admin page
 */
function lana_email_tester_admin_menu() {
	global $lana_email_tester_tool_page;

	/** add management (tool) page */
	$lana_email_tester_tool_page = add_management_page( __( 'Lana Email Tester', 'lana-email-tester' ), __( 'Lana Email Tester', 'lana-email-tester' ), 'manage_options', 'lana-email-tester.php', 'lana_email_tester_tool_page' );
}

add_action( 'admin_menu', 'lana_email_tester_admin_menu' );

/**
 * Lana Email Tester tool page
 */
function lana_email_tester_tool_page() {
	?>

    <div class="wrap">
        <h2><?php _e( 'Lana Email Tester', 'lana-email-tester' ); ?></h2>

        <h2 class="title"><?php _e( 'Email Server Settings', 'lana-email-tester' ); ?></h2>
        <pre><?php
			echo sprintf( '%s: %s', __( 'Operating System', 'lana-email-tester' ), php_uname( 's' ) );
			echo "\n";
			echo sprintf( '%s: %s', __( 'Sendmail Path', 'lana-email-tester' ), ini_get( 'sendmail_path' ) );
			?></pre>

        <h2 class='title'><?php _e( 'Email Header Settings', 'lana-email-tester' ); ?></h2>
        <pre><?php
			echo sprintf( 'MIME-Version: %s', '1.0' );
			echo "\n";
			echo sprintf( 'From: %s', get_bloginfo( 'admin_email' ) );
			echo "\n";
			echo sprintf( 'Content-Type: %s; charset="%s"', 'text/plain', get_option( 'blog_charset' ) );
			?></pre>

        <br/>

        <h2 class="title"><?php _e( 'Email Tester Settings', 'lana-email-tester' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lana_email_tester_send_test_wp_mail"/>

			<?php wp_nonce_field( 'lana_email_tester_send_test_wp_mail' ); ?>

            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="email-to">
							<?php _e( 'Email To:', 'lana-email-tester' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="email" name="email_to" id="email-to" class="regular-text" required/>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="send_email" id="send-test-email" class="button-primary"
                       value="<?php esc_attr_e( 'Send Email!', 'lana-email-tester' ); ?>"/>
            </p>
        </form>
    </div>
	<?php
}

/**
 * Lana Email Tester
 * send test wp mail
 */
function lana_email_tester_send_test_wp_mail() {

	check_admin_referer( 'lana_email_tester_send_test_wp_mail' );

	/** check capability */
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Sorry, you are not allowed to send test email.', 'lana-email-tester' ) );
	}

	$redirect_to = wp_get_referer();
	$email_to    = sanitize_email( $_POST['email_to'] );

	/** check valid email */
	if ( ! filter_var( $email_to, FILTER_VALIDATE_EMAIL ) ) {
		$redirect_to = add_query_arg( 'email_sent', 0, $redirect_to );
		$redirect_to = add_query_arg( 'error_code', 'not_valid_email', $redirect_to );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	$subject = sprintf( __( 'Test email from %s', 'lana-email-tester' ), get_bloginfo( 'url' ) );
	$message = sprintf( __( 'This test email proves that your WordPress installation at %s can send emails.', 'lana-email-tester' ), get_bloginfo( 'url' ) );

	$headers = array(
		sprintf( 'MIME-Version: %s', '1.0' ),
		sprintf( 'From: %s <%s>', __( 'Lana Codes - Email Tester', 'lana-email-tester' ), get_bloginfo( 'admin_email' ) ),
		sprintf( 'Content-Type: %s; charset="%s"', 'text/plain', get_option( 'blog_charset' ) ),
	);

	add_action( 'wp_mail_failed', function ( $error ) {
		wp_die( $error );
	} );

	add_filter( 'wp_mail_from', 'lana_email_tester_wp_mail_from', 10, 0 );
	add_filter( 'wp_mail_from_name', 'lana_email_tester_wp_mail_from_name', 10, 0 );

	$email_sent = wp_mail( $email_to, $subject, $message, $headers );

	remove_filter( 'wp_mail_from', 'lana_email_tester_wp_mail_from', 10 );
	remove_filter( 'wp_mail_from_name', 'lana_email_tester_wp_mail_from_name', 10 );

	$redirect_to = add_query_arg( 'email_sent', intval( $email_sent ), $redirect_to );
	wp_safe_redirect( $redirect_to );
	exit;
}

add_action( 'admin_post_lana_email_tester_send_test_wp_mail', 'lana_email_tester_send_test_wp_mail' );

/**
 * Lana Email Tester
 * from
 * @return string
 */
function lana_email_tester_wp_mail_from() {
	return get_bloginfo( 'admin_email' );
}

/**
 * Lana Email Tester
 * from name
 * @return string
 */
function lana_email_tester_wp_mail_from_name() {
	return get_bloginfo( 'name' );
}

/**
 * Lana Email Tester
 * admin notices
 */
function lana_email_tester_admin_notices() {
	global $lana_email_tester_tool_page;

	/** @var WP_Screen $screen */
	$screen = get_current_screen();

	if ( $lana_email_tester_tool_page != $screen->id ) {
		return;
	}

	if ( ! isset( $_GET['email_sent'] ) ) {
		return;
	}

	if ( $_GET['email_sent'] ) :
		?>

        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Test email sent.', 'lana-email-tester' ); ?></p>
        </div>

	<?php else : ?>

        <div class="notice notice-error is-dismissible">
            <p>
				<?php _e( 'Error while sending.', 'lana-email-tester' ); ?>

				<?php if ( isset( $_GET['error_code'] ) && 'not_valid_email' == $_GET['error_code'] ) : ?>
					<?php _e( 'This is not a valid email address.', 'lana-email-tester' ); ?>
				<?php endif; ?>
            </p>
        </div>

	<?php
	endif;
}

add_action( 'admin_notices', 'lana_email_tester_admin_notices', 10, 0 );