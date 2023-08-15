<?php
function add_client_role() {
    add_role('client', 'Client', array(
        'read' => true,
        'edit_profile' => true,
    ));
}
add_action('init', 'add_client_role');

if (!current_user_can('manage_options')) {
    add_filter('show_admin_bar', '__return_false');
}

function custom_login_form_shortcode()
{
    ob_start();
    ?>
    <form name="custom-login-form" id="custom-login-form" method="post">
		<div id="login-error" style="color: red;"></div>
        <input type="hidden" name="action" value="custom_login_action">
        <?php wp_nonce_field('custom-login-nonce', 'custom_login_nonce'); ?>

        <div class="input-container">
            <label for="username"><img src="http://s11.kanesherwell.com/wp-content/uploads/2023/08/user-03.png" alt="Username" /></label>
            <input type="text" id="username" name="log" placeholder="Your account" />
        </div>
        
        <div class="input-container">
            <label for="password"><img src="http://s11.kanesherwell.com/wp-content/uploads/2023/08/lock-01.png" alt="Password" /></label>
            <input type="password" id="password" name="pwd" placeholder="Password" />
        </div>
        
        <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url('/dash-two')); ?>" />
        <p><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Forgot Password?</a></p>
        <input type="submit" value="Log In" />
    </form>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        jQuery(document).ready(function($) {
            $('.input-container input[type="password"]').after('<span class="toggle-password"><img src="http://s11.kanesherwell.com/wp-content/uploads/2023/08/eye.png" alt="Toggle Password" /></span>');

            $('.input-container .toggle-password').click(function() {
                var input = $(this).prev('input');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                } else {
                    input.attr('type', 'password');
                }
            });

            $('.input-container input').on('focus', function() {
                $(this).prev('label').addClass('hide-placeholder');
            }).on('blur', function() {
                if ($(this).val() === '') {
                    $(this).prev('label').removeClass('hide-placeholder');
                }
            });

            $('#custom-login-form').on('submit', function(event) {
                event.preventDefault();

                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo esc_js(home_url('/dash-two')); ?>';
                        } else {
                            $('#login-error').text('Username or password is incorrect');
                        }
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('patients_login', 'custom_login_form_shortcode');

add_action('wp_ajax_custom_login_action', 'custom_login_action_handler');
add_action('wp_ajax_nopriv_custom_login_action', 'custom_login_action_handler');

function custom_login_action_handler()
{
    check_ajax_referer('custom-login-nonce', 'custom_login_nonce');

    $creds = array(
        'user_login'    => $_POST['log'],
        'user_password' => $_POST['pwd'],
        'remember'      => true,
    );

    $user = wp_signon($creds, false);

    if (!is_wp_error($user)) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}


add_action( 'show_user_profile', 'profile_fields_function' );
add_action( 'edit_user_profile', 'profile_fields_function' );
add_action( 'user_new_form', 'profile_fields_function' );

function profile_fields_function( $user ) {
    if ( is_a( $user, 'WP_User' ) ) {
        $address = get_user_meta( $user->ID, 'address', true );
        $city = get_user_meta( $user->ID, 'city', true );
        $state = get_user_meta( $user->ID, 'state', true );
        $postcode = get_user_meta( $user->ID, 'postcode', true );
    } else {
        $address = '';
        $city = '';
        $state = '';
        $postcode = '';
    }

    ?>
    <h3>Information</h3>
    <table class="form-table">
        <tr>
            <th><label for="address">Address</label></th>
            <td>
                <input type="text" name="address" id="address" value="<?php echo esc_attr( $address ) ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="city">City</label></th>
            <td>
                <input type="text" name="city" id="city" value="<?php echo esc_attr( $city ) ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="state">State</label></th>
            <td>
                <input type="text" name="state" id="state" value="<?php echo esc_attr( $state ) ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="postcode">Postcode</label></th>
            <td>
                <input type="text" name="postcode" id="postcode" value="<?php echo esc_attr( $postcode ) ?>" class="regular-text" />
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'personal_options_update', 'save_profile_fields_function' );
add_action( 'edit_user_profile_update', 'save_profile_fields_function' );
add_action( 'user_register', 'save_profile_fields_function' ); // For new user registration

function save_profile_fields_function( $user_id ) {
    if (isset($_POST['city'])) {
        $new_city = sanitize_text_field($_POST['city']);
        update_user_meta($user_id, 'city', $new_city);
    }
    
    if (isset($_POST['address'])) {
        $new_address = sanitize_text_field($_POST['address']);
        update_user_meta($user_id, 'address', $new_address);
    }
    
    if (isset($_POST['state'])) {
        $new_state = sanitize_text_field($_POST['state']);
        update_user_meta($user_id, 'state', $new_state);
    }
    
    if (isset($_POST['postcode'])) {
        $new_postcode = sanitize_text_field($_POST['postcode']);
        update_user_meta($user_id, 'postcode', $new_postcode);
    }
}

function add_custom_nonce_field() {
    wp_nonce_field('update-user-' . get_current_user_id(), '_wpnonce_user', true, true);
}
add_action('show_user_profile', 'add_custom_nonce_field');
add_action('edit_user_profile', 'add_custom_nonce_field');
add_action('user_new_form', 'add_custom_nonce_field');

function verify_custom_nonce() {
    if (isset($_POST['_wpnonce_user']) && !wp_verify_nonce($_POST['_wpnonce_user'], 'update-user-' . get_current_user_id())) {
        wp_die('Nonce verification failed.');
    }
}
add_action('personal_options_update', 'verify_custom_nonce');
add_action('edit_user_profile_update', 'verify_custom_nonce');
add_action('user_register', 'verify_custom_nonce');

function custom_profile_shortcode() {
    ob_start();
    ?>
    <div class="id-name" id="profile_user">
        <div class="name-mess">
			<?php
            if (is_user_logged_in()) {
				?>
            <h4>Welcome back,</h4>
            <?php
			}
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                echo '<h3>' . esc_html($current_user->display_name) . '</h3>';
            }else{
				?>
				<a href="#" id="login">Login</a>
				<?php
			}
            ?>
        </div>
		<?php
            if (is_user_logged_in()) {
		?>		
        <div class="ider-mg">
            <img src="http://s11.kanesherwell.com/wp-content/uploads/2023/08/Ellipse-1.png">
		<?php } ?>	
			<?php if (is_user_logged_in()) : ?>
            <div class="profile-dropdown">
               <a href="#" id="logout-link">Logout</a>
            </div>
        	<?php endif; ?>
        </div>
        
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_profile', 'custom_profile_shortcode');

function wp_footer_function(){
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        jQuery(document).ready(function($) {	
            var userLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
            
            if (userLoggedIn) {
                $(".profile-dropdown").css("display", "none");
                $("#menu-item-2006487 .w-nav-anchor").css("display", "none");
            } else {
                $(".profile-dropdown").css("display", "none");
                $("#menu-item-2006487 .w-nav-anchor").css("display", "block");
            }
            
            $('#logout-link').click(function(event) {
                event.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: ajaxurl, 
                    data: {
                        action: 'custom_logout'
                    },
                    success: function(response) {
                        window.location.href = '<?php echo esc_url(home_url()); ?>';
                    }
                });
            });
        });
    </script>
    <?php
}
add_action('wp_footer', 'wp_footer_function');

add_action('wp_ajax_custom_logout', 'custom_logout');
add_action('wp_ajax_nopriv_custom_logout', 'custom_logout');

function custom_logout() {
    wp_logout();
    die();
}

function clear_object_cache_on_logout() {
    global $wp_object_cache;

    if (is_a($wp_object_cache, 'WP_Object_Cache')) {
        $wp_object_cache->flush();
    }
}
add_action('wp_logout', 'clear_object_cache_on_logout');





function edit_user_details_form_shortcode() {
    if (!is_user_logged_in()) {
        return 'Please log in to edit your details.';
    }

    ob_start();
    $current_user = wp_get_current_user();	
    ?>
    <section class="table-form-main">
        <div class="container-fluid">
            <div class="table-form">
                <div class="form-hed">
                    <h2>Edit My details</h2>
                </div>
                <div class="form-det">
                    <div class="error_message" style="color: red;"></div>
                    <form id="edit-user-form" name="edit-user-form" method="post">
						<input type="hidden" name="action" value="edit_user_details_action">
						<div class="form-name-f">
							<input name="display_name" type="text" placeholder="Name" value="<?php echo esc_attr($current_user->display_name); ?>" />
							<input name="email" type="email" placeholder="Email address" value="<?php echo esc_attr($current_user->user_email); ?>" />
						</div>
						<div class="form-address-f">

							<input name="address" type="text" placeholder="Address" value="<?php echo get_user_meta( $current_user->ID, 'address', true ); ?>" />
							
							<input name="city" type="text" placeholder="City" value="<?php echo get_user_meta( $current_user->ID, 'city', true ); ?>" />
							
							<input name="state" type="text" placeholder="State" value="<?php echo get_user_meta( $current_user->ID, 'state', true ); ?>" />

							<input name="postcode" type="number" placeholder="Postcode" value="<?php echo get_user_meta( $current_user->ID, 'postcode', true ); ?>" />

						</div>
                        <div class="form-pass-f">
                            <input name="new_password" type="password" placeholder="New Password" />
                            <input name="confirm_new_password" type="password" placeholder="Confirm New Password" />
                        </div>

                        <input class="sub-btn" name="submit" type="submit" value="Submit" />
                        <?php wp_nonce_field('edit_user_details_nonce', 'edit_user_details_nonce'); ?>
                     </form>
                </div>
            </div>
        </div>
    </section>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
		jQuery(document).ready(function($) {
			$('#edit-user-form').on('submit', function(event) {
				event.preventDefault();
				var formData = $(this).serialize();
				$.ajax({
					type: 'POST',
					url: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
					data: formData,
					success: function(response) {
						$('.error_message').empty();
						if (response.success) {
							console.log(response);
							location.reload();
						}
						if (response.data && response.data.message) {
							$('.error_message').append(response.data.message);
						}
					}
				});
			});
		});
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('edit_user_details_form', 'edit_user_details_form_shortcode');

function edit_user_details_action_handler() {
    check_ajax_referer('edit_user_details_nonce', 'edit_user_details_nonce');
    
    $user_id = get_current_user_id();

    $updated_fields = array();

    if (isset($_POST['display_name'])) {
        $new_name = sanitize_text_field($_POST['display_name']);
        wp_update_user(array('ID' => $user_id, 'display_name' => $new_name));
    }

    if (isset($_POST['email'])) {
        $new_email = sanitize_email($_POST['email']);
        $current_email = get_the_author_meta('user_email', $user_id);

        if ($new_email !== $current_email) {
            wp_update_user(array('ID' => $user_id, 'user_email' => $new_email));
            $updated_fields[] = 'user_email';
        }
    }

    $new_password = sanitize_text_field($_POST['new_password']);
    $confirm_new_password = sanitize_text_field($_POST['confirm_new_password']);

    if (!empty($new_password) && $new_password === $confirm_new_password) {
        wp_set_password($new_password, $user_id);
        $updated_fields[] = 'password';
    } elseif (!empty($new_password) && $new_password !== $confirm_new_password) {
        wp_send_json_error(array('message' => 'Passwords do not match.'));
    }

    $fields_to_update = array('address', 'city', 'state', 'postcode');
    foreach ($fields_to_update as $field) {
        if (isset($_POST[$field])) {
            $new_value = sanitize_text_field($_POST[$field]);
            $current_value = get_user_meta($user_id, $field, true);

            if ($new_value !== $current_value) {
                update_user_meta($user_id, $field, $new_value);
                $updated_fields[] = $field;
            }
        }
    }

    wp_send_json_success(array('message' => 'Updated fields successfully.'));
}
add_action('wp_ajax_edit_user_details_action', 'edit_user_details_action_handler');
add_action('wp_ajax_nopriv_edit_user_details_action', 'edit_user_details_action_handler');


function forgot_password_shortcode() {
    ob_start();
    ?>
    <form id="forgot-password-form" name="forgot-password-form" method="post">
        <input type="email" name="user_email" placeholder="Enter your email" required />
        <?php wp_nonce_field('forgot_password_nonce', 'forgot_password_nonce'); ?>
        <input type="hidden" name="action" value="forgot_password_action"> <!-- Add this line -->
        <input type="submit" value="Generate Reset Link" />
    </form>
    <div class="reset-password-form"></div>
    <div class="message"></div>
    <script>
        jQuery(document).ready(function($) {
            $('#forgot-password-form').on('submit', function(event) {
                event.preventDefault();
                var formData = $(this).serialize();
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: formData,
                    success: function(response) {
                        $('.reset-password-form').html(response.data); // Display the form
                        $('.message').empty();
                        bindResetForm(); // Call a function to bind the reset form submit event
                    }
                });
            });

            function bindResetForm() {
                $('#reset-password-form').on('submit', function(event) {
                    event.preventDefault();
                    var resetFormData = $(this).serialize();
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        data: resetFormData,
                        success: function(response) {
                            $('.message').html(response); // Display success or error message
                        }
                    });
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('forgot_password', 'forgot_password_shortcode');


function handle_forgot_password() {
    check_ajax_referer('forgot_password_nonce', 'forgot_password_nonce');

    $user_email = sanitize_email($_POST['user_email']);

    if (empty($user_email)) {
        wp_send_json_error('Please provide a valid email.');
    }

    $user = get_user_by('email', $user_email);

    if (!$user) {
        wp_send_json_error('No user found with this email address.');
    }

    $reset_password_form = '
        <form id="reset-password-form" name="reset-password-form" method="post">
            <input type="hidden" name="user_id" value="' . $user->ID . '" />
            <input type="text" name="user_name" placeholder="Username" required />
            <input type="password" name="new_password" placeholder="New Password" required />
            <input type="password" name="confirm_new_password" placeholder="Confirm New Password" required />
            ' . wp_nonce_field('reset_password_nonce', 'reset_password_nonce', true, false) . '
            <input type="hidden" name="action" value="reset_password_action"> <!-- Add this line -->
            <input type="submit" value="Reset Password" />
        </form>
    ';

    wp_send_json_success($reset_password_form);
}
add_action('wp_ajax_forgot_password_action', 'handle_forgot_password');
add_action('wp_ajax_nopriv_forgot_password_action', 'handle_forgot_password');

function handle_reset_password() {
    check_ajax_referer('reset_password_nonce', 'reset_password_nonce');

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $user_name = sanitize_text_field($_POST['user_name']);
    $new_password = sanitize_text_field($_POST['new_password']);
    $confirm_new_password = sanitize_text_field($_POST['confirm_new_password']);

    if (empty($new_password) || empty($confirm_new_password)) {
        wp_send_json_error('Please provide both new password and confirmation.');
    }

    if ($new_password !== $confirm_new_password) {
        wp_send_json_error('Passwords do not match.');
    }

    $user = get_user_by('id', $user_id);

    if (!$user) {
        wp_send_json_error('User not found.');
    }

    if ($user_name !== $user->user_login) {
        wp_send_json_error('Username does not match.');
    }

    wp_set_password($new_password, $user_id);

    wp_send_json_success('Password successfully reset.');
}
add_action('wp_ajax_reset_password_action', 'handle_reset_password');
add_action('wp_ajax_nopriv_reset_password_action', 'handle_reset_password');


