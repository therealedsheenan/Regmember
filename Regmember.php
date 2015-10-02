<?php
/*
  Plugin Name: Regmember
  Plugin URI: https://github.com/therealedsheenan/Regmember
  Description: User registration plugin for wordpress
  Version: 1.0
  Author: Sheenan Tenepre
 */

 function registration_form( $username, $password, $email, $telephone, $first_name, $last_name, $company, $address ) {
    echo '
    <style>
    div {
        margin-bottom:2px;
    }

    input{
        margin-bottom:4px;
    }
    </style>
    ';

    echo '
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
    <div>
    <label for="username">Username <strong>*</strong></label>
    <input type="text" name="username" value="' . ( isset( $_POST['username'] ) ? $username : null ) . '">
    </div>

    <div>
    <label for="password">Password <strong>*</strong></label>
    <input type="password" name="password" value="' . ( isset( $_POST['password'] ) ? $password : null ) . '">
    </div>

    <div>
    <label for="email">Email <strong>*</strong></label>
    <input type="text" name="email" value="' . ( isset( $_POST['email']) ? $email : null ) . '">
    </div>

    <div>
    <label for="telephone">Telephone</label>
    <input type="text" name="tel" value="' . ( isset( $_POST['tel']) ? $telephone : null ) . '">
    </div>

    <div>
    <label for="firstname">First Name</label>
    <input type="text" name="fname" value="' . ( isset( $_POST['fname']) ? $first_name : null ) . '">
    </div>

    <div>
    <label for="website">Last Name</label>
    <input type="text" name="lname" value="' . ( isset( $_POST['lname']) ? $last_name : null ) . '">
    </div>

    <div>
    <label for="company">Company Name</label>
    <input type="text" name="cname" value="' . ( isset( $_POST['cname']) ? $company : null ) . '">
    </div>

    <div>
    <label for="address">Address</label>
    <textarea name="address">' . ( isset( $_POST['address']) ? $address : null ) . '</textarea>
    </div>
    <input type="submit" name="submit" value="Register"/>
    </form>
    ';
} //end of registration form


function registration_validation ( $username, $password, $email, $telephone, $first_name, $last_name, $company, $address ) {
    global $reg_errors;
    $reg_errors = new WP_Error;

    if ( empty( $username ) || empty( $password ) || empty( $email ) ) {
        $reg_errors->add('field', 'Please fill out the required form.');
    }

    if ( 4 > strlen( $username ) ) {
        $reg_errors->add( 'username_length', 'Username too short. At least 4 characters is required' );
    }

    if ( username_exists( $username ) ) {
        $reg_errors->add('user_name', 'Please use another username. That username already exists!');
    }

    if ( ! validate_username( $username ) ) {
        $reg_errors->add( 'username_invalid', 'Sorry, the username you entered is not valid' );
    }

    if ( 5 > strlen( $password ) ) {
        $reg_errors->add( 'password', 'Password length must be greater than 5' );
    }

    if ( !is_email( $email ) ) {
        $reg_errors->add( 'email_invalid', 'Email is not valid' );
    }

    if ( email_exists( $email ) ) {
        $reg_errors->add( 'email', 'Email Already in use' );
    }

    if ( is_wp_error( $reg_errors ) ) {
        foreach ( $reg_errors->get_error_messages() as $error ) {
            echo '<div>';
            echo '<strong>ERROR</strong>:';
            echo $error . '<br/>';
            echo '</div>';

        }
    }
} //end of registration_validation function

function complete_registration() {
    global $reg_errors, $username, $password, $email, $telephone, $first_name, $last_name, $company, $address;
    if ( 1 > count( $reg_errors->get_error_messages() ) ) {
        $userdata = array(
        'user_login'    =>   $username,
        'user_pass'     =>   $password,
        'user_email'    =>   $email,
        'telephone'     =>   $telephone,
        'first_name'    =>   $first_name,
        'last_name'     =>   $last_name,
        'company'       =>   $company,
        'address'       =>   $address,
        'role'          =>   'member'
        );
        $user = wp_insert_user( $userdata );
        echo 'Registration complete. Goto <a href="' . get_site_url() . '/wp-login.php">login page</a>.';
    }
}  //end of complete_registration function

function custom_registration_function() {
    if ( isset($_POST['submit'] ) ) {
        registration_validation(
        $_POST['username'],
        $_POST['password'],
        $_POST['email'],
        $_POST['tel'],
        $_POST['fname'],
        $_POST['lname'],
        $_POST['cname'],
        $_POST['address']
        );

        // sanitize user form input
        global $username, $password, $email, $telephone, $first_name, $last_name, $company, $address;
        $username   =   sanitize_user( $_POST['username'] );
        $password   =   esc_attr( $_POST['password'] );
        $email      =   sanitize_email( $_POST['email'] );
        $telephone  =   sanitize_text_field( $_POST['tel'] );
        $first_name =   sanitize_text_field( $_POST['fname'] );
        $last_name  =   sanitize_text_field( $_POST['lname'] );
        $company    =   sanitize_text_field( $_POST['company'] );
        $address    =   esc_textarea( $_POST['address'] );

        // call @function complete_registration to create the user
        // only when no WP_error is found
        complete_registration(
            $username,
            $password,
            $email,
            $telephone,
            $first_name,
            $last_name,
            $company,
            $address
        );
    }

    registration_form(
        $username,
        $password,
        $email,
        $telephone,
        $first_name,
        $last_name,
        $company,
        $address
    );
}

// Register a new shortcode: [cr_custom_registration]
add_shortcode( 'cr_custom_registration', 'custom_registration_shortcode' );
 
// The callback function that will replace [book]
function custom_registration_shortcode() {
    ob_start();
    custom_registration_function();
    return ob_get_clean();
}
