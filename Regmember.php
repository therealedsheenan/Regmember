<?php
/*
  Plugin Name: Regmember
  Plugin URI: https://github.com/therealedsheenan/Regmember
  Description: User registration plugin for wordpress
  Version: 1.0
  Author: Sheenan Tenepre
 */


class Regmember {
    const PREFIX = 'email-confirmation-';

    //declares global values
    function __construct () {
        global $reg_errors, $username, $password, $email, $telephone, $first_name, $last_name, $company, $address;
    }

    /*
        - Displays the UI of the registration form.
            on editing the form, take note of the label, value and name of field
            these fields should be the exact names/value to be called upon submitting the form
    */
    public function _formRegistration ( $username, $password, $email, $telephone, $first_name, $last_name, $company, $address ) {

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
    }

    /*
        - Creates an event listener if the submit button is clicked.
        - Upon submission of form, this function calls the _validateRegistration() function
            to detect errors based on the rules stated on the said function.
        - These rules can be edited based on the developer's preferences.
        - The user fields will be validated for the last time to avoid unnecesarry database query injects,
            invalid characters and etc...
        - After all the validations, it will call the confirmRegistration function
        - If not submitted, it will return a NULL value
    */
    public function _submitRegistration () {
        if ( isset($_POST['submit'] ) ) {
            $this->_validateRegistration(
                $_POST['username'],
                $_POST['password'],
                $_POST['email'],
                $_POST['tel'],
                $_POST['fname'],
                $_POST['lname'],
                $_POST['cname'],
                $_POST['address']
            );

            global $username, $password, $email, $telephone, $first_name, $last_name, $company, $address;
            $username   =   sanitize_user( $_POST['username'] );
            $password   =   esc_attr( $_POST['password'] );
            $email      =   sanitize_email( $_POST['email'] );
            $telephone  =   sanitize_text_field( $_POST['tel'] );
            $first_name =   sanitize_text_field( $_POST['fname'] );
            $last_name  =   sanitize_text_field( $_POST['lname'] );
            $company    =   sanitize_text_field( $_POST['company'] );
            $address    =   esc_textarea( $_POST['address'] );

            $this->_confirmRegistration(
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
        return;
    }

    /*
        - checks if the token if available.
        - if available, saves user data to database
    */
    public function _checkTokenRegistration ( $token ) {
        $data = get_option( self::PREFIX .'data' );
        $userData = $data[$token];
        if ( isset( $userData ) ) {
            $this->_completeRegistration( $userData );
            unset( $data[$token] );
            update_option( self::PREFIX .'data', $data );
        }
        return $userData;
    } //end of _checkTokenRegistration()

    /*
        - Sends confirmation email to the email field provided by the user upon fill-up.
    */
    private function _confirmRegistration ( ){
        global $reg_errors, $username, $password, $email, $telephone, $first_name, $last_name, $company, $address;
        if ( 1 > count( $reg_errors->get_error_messages() ) ) {
            $headers = "From: admin <admin@systemdev847.com>";
            $subject = "Asianpropertyawards Confirmation";
            $message = "Greetings! \r\n \r\n";
            $message .= "Your registration at Asianpropertyawards.com is confirmed! \r\n \r\n";
            $message .= "Please visit the following link to complete your registration: \r\n \r\n";
            $message .= home_url('/register') . "?token=%s";

            $this->_sendEmailRegistration( $email, $subject, $message, $headers );
            echo 'We have sent a confirmation link to the E-mail address you provided. Thank you.';
        }
    } //end of _confirmRegistration()

    /*
        - submits data to database
    */
    private function _completeRegistration ( $userData ) {
        if ( !empty( $userData ) ) {
            $formatData = array(
                'user_login'    =>   $userData['username'],
                'user_pass'     =>   $userData['password'],
                'user_email'    =>   $userData['email'],
                'telephone'     =>   $userData['tel'],
                'first_name'    =>   $userData['fname'],
                'last_name'     =>   $userData['lname'],
                'company'       =>   $userData['cname'],
                'address'       =>   $userData['address'],
                'role'          =>   'member'
            );
            $user = wp_insert_user( $formatData );
            return;
        }
    } //end of _completeRegistration()

    /*
        - Declares the validation rules of the for fields
    */
    private function _validateRegistration ( $username, $password, $email, $telephone, $first_name, $last_name, $company, $address ) {
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

        if ( !validate_username( $username ) ) {
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
    } //end of _validateRegistration()

    /*
        - Sends e-mail with unique token
    */
    private function _sendEmailRegistration ( $to, $subject, $message, $headers ) {
        $token = sha1( uniqid() );
        $oldData = get_option( self::PREFIX .'data' ) ?: array();
        $data = array();
        $data[$token] = $_POST;
        update_option( self::PREFIX .'data', array_merge( $oldData, $data ) );

        wp_mail( $to, $subject, sprintf( $message, $token ), $headers );
    } //end of _sendEmailRegistration()

}

// Register a new shortcode: [cr_custom_registration]
add_shortcode( 'cr_custom_registration', 'custom_registration_shortcode' );

//main function call
function custom_registration_shortcode() {
    ob_start();
    $reg = new Regmember();
    if ( $reg->_checkTokenRegistration($_GET['token']) ) {
        echo 'Registration complete. Goto <a href="' . get_site_url() . '/wp-login.php">login page</a>.';
    } else {
        $reg->_submitRegistration();
        $reg->_formRegistration(
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
    return ob_get_clean();
}
