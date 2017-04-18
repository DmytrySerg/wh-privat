<?php
define('UA_FILE', __FILE__);
define('UA_ROOT', dirname(__FILE__));
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
require_once($DOCUMENT_ROOT . '/wp-load.php');

if ($_POST['action'] == 'ua_login_in') {
    $output = ua_login_in();
    echo $output;
}

if ($_POST['action'] == 'ua_register') {
    $output_reg = ua_register();
    echo $output_reg;
}

if ($_POST['action'] == 'ua_profile_update') {
    $output_profile_update = ua_profile_update();
    echo $output_profile_update;
}

if ($_POST['action'] == 'resend_mail') {
    $output_resend = resend_mail();
    echo $output_resend;
}
if ($_POST['action'] == 'enquiry_offer') {
    $output_resend = enquiry_offer();
    echo $output_resend;
}

if ($_POST['action'] == 'enquiry_service') {
    $output_resend = enquiry_service();
    echo $output_resend;
}



function ua_login_in()
{

    $return = array();
    $username = $_REQUEST['username'];
    $password = $_REQUEST['password'];

    /*$username = 'Aleksandr Chumak';
    $password = 'Z%M6l)zo6fd)Ilm)WkeXyyvi';*/

    $return['username'] = strval($username);
    $return['password'] = $password;


    // this returns the user ID and other info from the user name

    $user = get_user_by('login', $username);
    $user_by_email = get_user_by('email', $username);

    if (!isset($password) || $password == '') {
        // if no password was entered
        $return['no_pass'] = 'Please enter a password';
    }

    if (!$user && !$user_by_email) {
        // if the user name doesn't exist
        $return['username_error'] = 'Invalid username';
    } elseif ($user_by_email){
        // check the user's login with their password
        if (!wp_check_password($password, $user_by_email->user_pass, $user_by_email->ID)) {
            // if the password is incorrect for the specified user
            $return['inc_pass'] = 'Incorrect password';
        } else {
            $return['success_auth'] = 'Logged';
            wp_setcookie($user_by_email->user_login, $password, true);
            wp_set_current_user($user_by_email->ID, $password);
            do_action('wp_login', $user_by_email->user_login);
        }
    }elseif ($user){
        // check the user's login with their password
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            // if the password is incorrect for the specified user
            $return['inc_pass'] = 'Incorrect password';
        } else {
            $return['success_auth'] = 'Logged';
            wp_setcookie($username, $password, true);
            wp_set_current_user($user->ID, $password);
            do_action('wp_login', $username);
        }
    }


    wp_send_json($return);

    die();
}

function ua_register()
{

    $return = array();
    $error = false;
    $username = $_REQUEST['register-username'];
    $first_name = $_REQUEST['register-first-name'];
    $last_name = $_REQUEST['register-last-name'];
    $email = $_REQUEST['register-email'];
    $job_title = $_REQUEST['register-job-title'];
    $organisation = $_REQUEST['register-organisation'];
    $password = $_REQUEST['register-password'];
    $confirm_pass = $_REQUEST['register-confirm-password'];
    $register_subscribe = $_REQUEST['register-subscribe'];

    $return['register_username'] = $username;
    $return['register_first_name'] = $first_name;
    $return['register_last_name'] = $last_name;
    $return['register_email'] = $email;
    $return['register_job_title'] = $job_title;
    $return['register_ogranisation'] = $organisation;
    $return['register_password'] = $password;
    $return['register_conf_password'] = $confirm_pass;


    if (username_exists($username)) {
        // Username already registered
        $return['username_error_reg'] = 'Username already registered';
        $error = true;
    }

    if (!validate_username($username)) {
        // Username already registered
        $return['username_error_inv'] = 'Invalid username';
        $error = true;
    }

    if ($username == '') {
        // Username already registered
        $return['username_error_empty'] = 'Please enter a username';
        $error = true;
    }

    if ($first_name == '') {
        // Username already registered
        $return['first_name_error'] = 'Please enter a First name';
        $error = true;
    }

    if ($last_name == '') {
        // Username already registered
        $return['last_name_error'] = 'Please enter a Second name';
        $error = true;
    }


    if (!is_email($email)) {
        //invalid email
        $return['email_error'] = 'Invalid email';
        $error = true;
    }

    if (email_exists($email)) {
        //invalid email
        $return['email_exist'] = 'Email already registered';
        $error = true;
    }

    if ($job_title == '') {
        // Username already registered
        $return['job_title_error'] = 'Please enter a Job title';
        $error = true;
    }

    if ($organisation == '') {
        // Username already registered
        $return['organisation_error'] = 'Please enter an Ogranization name';
        $error = true;
    }

    if ($password == '') {
        // passwords do not match
        $return['pass_error_empty'] = 'Please enter a password';
        $error = true;
    }
    if ($password != $confirm_pass) {
        // passwords do not match
        $return['pass_error_confirm'] = 'Passwords do not match';
        $error = true;
    }
    if (!$error) {

        if (get_option('users_can_register')) {
            //CREATE NEW USER
            $new_user_id = wp_insert_user(array(
                    'user_login' => $username,
                    'user_pass' => $password,
                    'user_email' => $email,
                    'first_name' => $first_name,
                    'job_title' => $job_title,
                    'organisation' => $organisation,
                    'last_name' => $last_name,
                    'user_registered' => date('Y-m-d H:i:s'),
                    'role' => 'subscriber',
                )
            );
            update_usermeta($new_user_id, 'updates_subscribe', $register_subscribe);
            if ($new_user_id) {
                // send an email to the admin alerting them of the registration
                wp_new_user_notification($new_user_id);

            }
            $return['register_success'] = 'Register success';
            $return['user_id_new'] = $new_user_id;
        }
    }


    wp_send_json($return);

    die();
}

function ua_profile_update()
{

    $return = array();
    $error = false;

    $profile_id = $_REQUEST['profile-id'];
    $first_name = $_REQUEST['profile-first-name'];
    $last_name = $_REQUEST['profile-last-name'];
    $email = $_REQUEST['profile-email'];
    $job_title = $_REQUEST['profile-job-title'];
    $organisation = $_REQUEST['profile-organisation'];


    $return['profile_first_name'] = $first_name;
    $return['profile_id'] = $profile_id;
    $return['profile_last_name'] = $last_name;
    $return['profile_email'] = $email;
    $return['profile_job_title'] = $job_title;
    $return['profile_ogranisation'] = $organisation;

    if ($first_name == '') {
        $return['first_name_error'] = 'Please enter a First name';
        $error = true;
    }

    if ($last_name == '') {
        $return['last_name_error'] = 'Please enter a Second name';
        $error = true;
    }


    if (!is_email($email)) {
        //invalid email
        $return['email_error'] = 'Invalid email';
        $error = true;
    }

    if ($job_title == '') {
        // Username already registered
        $return['job_title_error'] = 'Please enter a Job title';
        $error = true;
    }

    if ($organisation == '') {
        // Username already registered
        $return['organisation_error'] = 'Please enter an Ogranization name';
        $error = true;
    }

    if (!$error) {

        //UPDATE USER
        $updated_id = wp_update_user(array(
                'ID' => $profile_id,
                'user_email' => $email,
                'first_name' => $first_name,
                'job_title' => $job_title,
                'organisation' => $organisation,
                'last_name' => $last_name,
                'user_registered' => date('Y-m-d H:i:s'),
                /*'role' => 'administrator',*/
            )
        );
        if (is_wp_error($updated_id)) {
            $return['update_error'] = 'Update error';
        } else {
            $return['update_success'] = 'Profile details successfully updated.';
        }

    }


    wp_send_json($return);

    die();
}

function resend_mail()
{
    $return = array();
    $new_user_id = $_REQUEST['resend_user_id'];
    wp_new_user_notification($new_user_id, null, 'user');

    $return['success_resend'] = 'Mail sent successfully.';

    wp_send_json($return);

    die();
}

function enquiry_offer()
{

    $return = array();
    $error = false;

    $first_name = $_REQUEST['enquiry-first-name'];
    $last_name = $_REQUEST['enquiry-last-name'];
    $email = $_REQUEST['enquiry-email'];
    $job_title = $_REQUEST['enquiry-job-title'];
    $organisation = $_REQUEST['enquiry-organisation'];
    $enquiry_subscribe = $_REQUEST['enquiry-subscribe'];
    $enquiry_name = $_REQUEST['enquiry-name'];
    $user = get_user_by('email', $email);

    $return['enquiry_first_name'] = $first_name;
    $return['enquiry_last_name'] = $last_name;
    $return['enquiry_email'] = $email;
    $return['enquiry_job_title'] = $job_title;
    $return['enquiry_ogranisation'] = $organisation;


    if ($first_name == '') {
        // Username already registered
        $return['first_name_error'] = 'Please enter a First name';
        $error = true;
    }

    if ($last_name == '') {
        // Username already registered
        $return['last_name_error'] = 'Please enter a Second name';
        $error = true;
    }


    if (!is_email($email)) {
        //invalid email
        $return['email_error'] = 'Invalid email';
        $error = true;
    }

    if ($job_title == '') {
        // Username already registered
        $return['job_title_error'] = 'Please enter a Job title';
        $error = true;
    }

    if ($organisation == '') {
        // Username already registered
        $return['organisation_error'] = 'Please enter an Ogranization name';
        $error = true;
    }

    if (!$error) {
        wp_enquiry_mail($email, $enquiry_name, $first_name);
        $return['register_success'] = 'Register success';
    }


    wp_send_json($return);

    die();
}

function wp_enquiry_mail($email, $offer, $first_name)
{
    $user = get_user_by('email', $email);
    if ($user) {
        $mail_email = $user->user_email;
        $first_name = $user->display_name;
    } else {
        $mail_email = $email;
    }
    $message = '<table cellpadding="0" cellspacing="0" width="100%" >
            <tr>
                <td>
                    <table align="center" cellpadding="0" cellspacing="0"  style="border-collapse: collapse; width: 100%; max-width: 850px;">
                        <tr>
                            <td height="100">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="padding-left: 20px; padding-right: 20px;">
                                    <tr>
                                        <td width="80%" valign="bottom" style="font-weight: 100;letter-spacing: 5px;padding-bottom: 10px; font-size:24px; font-family: \'Open Sans\', \'Helvetica\', sans-serif;">
                                            '. get_field('make_enq_title_mail','option') .'
                                        </td>

                                        <td width="20%" align="right">
                                            <a href="http://www.100open.com/">
                                                <img src="http://www.100open.com/wp-content/themes/100open/img/logo-green.png" alt="100Open" width="80" height="80" style="display: block;" border="0" />
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
            <tr>
                <td bgcolor="#1DD69C" height="20"></td>
            </tr>
            
            
             <tr>
                <td bgcolor="#EBEBEB" style="padding: 10px; padding-top: 0px;">
                    <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" width="100%">
                        <tbody><tr>
                            <td>
                                <table align="center" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 850px; padding-left: 20px; padding-right: 20px; padding-top: 40px; padding-bottom: 80px; font-weight: 100; font-size:15px; padding-left: 20px; padding-right: 20px;">
                                    <tbody><tr>
                                        <td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Thanks for your interest in ' . $offer . '</td>
                                    </tr>
                                    
                                    <tr>
                                        <td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">
                                        
                                               '. get_field('make_enq_text_mail','option') .'                                  
                                        </td>
                                   </tr> 
                                   
                                </tbody></table>
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
            
            <!-- Footer start -->
            <tr>
                <td bgcolor="#EBEBEB" style="padding: 10px; padding-top: 0px;">
                    <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                        <tr>
                            <td>
                                <table align="center" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 700px; padding-left: 20px; padding-right: 20px; padding-bottom: 40px;">
                                    <tr>
                                        <table width="100" align="left" border="0" cellpadding="0" cellspacing="0" style="padding-left: 10px; padding-top: 20px;">
                                            <tr>
                                                <td valign="top">
                                                    <img src="http://www.100open.com/wp-content/themes/100open/img/logo-footer.png" alt="100Open" width="70" style="display: block;" border="0"/>
                                                </td>
                                            </tr>
                                        </table>

                                        <table width="360" align="left" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <table cellpadding="0" cellspacing="0" width="100%" style="font-size: 12px; font-weight: 100; padding-left: 10px; padding-top: 20px;">
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">New Wing</td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Somerset House</td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Strand </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">London </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">WC2R 1LA </td></tr>
                                                        <tr><td>&nbsp; </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">+44 (0)20 7759 1050  </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">hello@100open.com </td></tr>
                                                        <tr><td>&nbsp; </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Want to change how you receive these emails? </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">You can <a href="#" style="color: #F5115F; text-decoration: none;"><font color="#F5115F">update your preferences</font></a> or  <a href="#" style="color: #F5115F; text-decoration: none;">  <font color="#F5115F">unsubscribe</font></a> from this list. </td></tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table width="120" align="left" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <table border="0" cellpadding="0" cellspacing="0" align="right" style="padding-bottom: 20px; padding-top: 20px; padding-right: 20px;">
                                                    <tr>
                                                        <td>
                                                            <a href="https://twitter.com/100open/">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-01.png" alt="Twitter" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://www.linkedin.com/company/100-open?trk=tyah">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-02.png" alt="LinkedIn" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://www.flickr.com/photos/100open/">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-03.png" alt="Flickr" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://vimeo.com/open100">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-05.png" alt="Vimeo" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://plus.google.com/113602379327483567799">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-04.png" alt="Google+" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </tr>
                                        </table>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- Footer end -->
        </table>';


    wp_mail($mail_email, sprintf(__('Thank you for enquiring about [%s]'), $offer), $message);
}

function enquiry_service()
{

    $return = array();
    $error = false;

    $first_name = $_REQUEST['enquiry-first-name'];
    $last_name = $_REQUEST['enquiry-last-name'];
    $email = $_REQUEST['enquiry-email'];
    $job_title = $_REQUEST['enquiry-job-title'];
    $organisation = $_REQUEST['enquiry-organisation'];
    $enquiry_subscribe = $_REQUEST['enquiry-subscribe'];
    $enquiry_name = $_REQUEST['enquiry-name'];
    $user = get_user_by('email', $email);

    $return['enquiry_first_name'] = $first_name;
    $return['enquiry_last_name'] = $last_name;
    $return['enquiry_email'] = $email;
    $return['enquiry_job_title'] = $job_title;
    $return['enquiry_ogranisation'] = $organisation;


    if ($first_name == '') {
        // Username already registered
        $return['first_name_error'] = 'Please enter a First name';
        $error = true;
    }

    if ($last_name == '') {
        // Username already registered
        $return['last_name_error'] = 'Please enter a Second name';
        $error = true;
    }


    if (!is_email($email)) {
        //invalid email
        $return['email_error'] = 'Invalid email';
        $error = true;
    }

    if ($job_title == '') {
        // Username already registered
        $return['job_title_error'] = 'Please enter a Job title';
        $error = true;
    }

    if ($organisation == '') {
        // Username already registered
        $return['organisation_error'] = 'Please enter an Ogranization name';
        $error = true;
    }

    if (!$error) {
        wp_enquiry_service_mail($email, $enquiry_name, $first_name);
        $return['register_success'] = 'Register success';
    }


    wp_send_json($return);

    die();
}

function wp_enquiry_service_mail($email, $offer, $first_name)
{
    $user = get_user_by('email', $email);
    if ($user) {
        $mail_email = $user->user_email;
        $first_name = $user->display_name;
    } else {
        $mail_email = $email;
    }
    $message = '<table cellpadding="0" cellspacing="0" width="100%" >
            <tr>
                <td>
                    <table align="center" cellpadding="0" cellspacing="0"  style="border-collapse: collapse; width: 100%; max-width: 850px;">
                        <tr>
                            <td height="100">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="padding-left: 20px; padding-right: 20px;">
                                    <tr>
                                        <td width="80%" valign="bottom" style="font-weight: 100;letter-spacing: 5px;padding-bottom: 10px; font-size:24px; font-family: \'Open Sans\', \'Helvetica\', sans-serif;">
                                            '. get_field('make_enq_title_mail_service','option') .'
                                        </td>

                                        <td width="20%" align="right">
                                            <a href="http://www.100open.com/">
                                                <img src="http://www.100open.com/wp-content/themes/100open/img/logo-green.png" alt="100Open" width="80" height="80" style="display: block;" border="0" />
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
            <tr>
                <td bgcolor="#1DD69C" height="20"></td>
            </tr>
            
            
             <tr>
                <td bgcolor="#EBEBEB" style="padding: 10px; padding-top: 0px;">
                    <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" width="100%">
                        <tbody><tr>
                            <td>
                                <table align="center" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 850px; padding-left: 20px; padding-right: 20px; padding-top: 40px; padding-bottom: 80px; font-weight: 100; font-size:15px; padding-left: 20px; padding-right: 20px;">
                                    <tbody>
                                    <tr>
                                        <td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Hello, ' . $first_name . '</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Thanks for your interest in ' . $offer . '</td>
                                    </tr>
                                    
                                    <tr>
                                        <td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">
                                        
                                               '. get_field('make_enq_text_mail_service','option') .'                                  
                                        </td>
                                   </tr> 
                                   
                                </tbody></table>
                            </td>
                        </tr>
                    </tbody></table>
                </td>
            </tr>
            
            <!-- Footer start -->
            <tr>
                <td bgcolor="#EBEBEB" style="padding: 10px; padding-top: 0px;">
                    <table bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                        <tr>
                            <td>
                                <table align="center" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 700px; padding-left: 20px; padding-right: 20px; padding-bottom: 40px;">
                                    <tr>
                                        <table width="100" align="left" border="0" cellpadding="0" cellspacing="0" style="padding-left: 10px; padding-top: 20px;">
                                            <tr>
                                                <td valign="top">
                                                    <img src="http://www.100open.com/wp-content/themes/100open/img/logo-footer.png" alt="100Open" width="70" style="display: block;" border="0"/>
                                                </td>
                                            </tr>
                                        </table>

                                        <table width="360" align="left" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <table cellpadding="0" cellspacing="0" width="100%" style="font-size: 12px; font-weight: 100; padding-left: 10px; padding-top: 20px;">
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">New Wing</td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Somerset House</td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Strand </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">London </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">WC2R 1LA </td></tr>
                                                        <tr><td>&nbsp; </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">+44 (0)20 7759 1050  </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">hello@100open.com </td></tr>
                                                        <tr><td>&nbsp; </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">Want to change how you receive these emails? </td></tr>
                                                        <tr><td style="font-family: \'Open Sans\', \'Helvetica\', sans-serif;">You can <a href="#" style="color: #F5115F; text-decoration: none;"><font color="#F5115F">update your preferences</font></a> or  <a href="#" style="color: #F5115F; text-decoration: none;">  <font color="#F5115F">unsubscribe</font></a> from this list. </td></tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table width="120" align="left" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <table border="0" cellpadding="0" cellspacing="0" align="right" style="padding-bottom: 20px; padding-top: 20px; padding-right: 20px;">
                                                    <tr>
                                                        <td>
                                                            <a href="https://twitter.com/100open/">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-01.png" alt="Twitter" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://www.linkedin.com/company/100-open?trk=tyah">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-02.png" alt="LinkedIn" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://www.flickr.com/photos/100open/">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-03.png" alt="Flickr" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://vimeo.com/open100">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-05.png" alt="Vimeo" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>

                                                        <td>
                                                            <a href="https://plus.google.com/113602379327483567799">
                                                                <img src="http://www.100open.com/wp-content/uploads/2016/04/Social-media-icons-04.png" alt="Google+" width="30" height="30" style="display: block; margin-right:4px;" border="0" />
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </tr>
                                        </table>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <!-- Footer end -->
        </table>';


    wp_mail($mail_email, sprintf(__('Thank you for enquiring about our [%s] service'), $offer), $message);
}