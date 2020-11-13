<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Email OTP authentication plugin.
 *
 * @see self::user_login()
 * @package    auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

use core\output\notification;

/**
 * Email OTP authentication plugin.
 *
 * @see self::user_login()
 * @package    auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_emailotp extends auth_plugin_base {

    /**
     * The name of the component. Used by the configuration.
     */
    const COMPONENT_NAME = 'auth_emailotp';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'emailotp';
        $this->config = get_config(self::COMPONENT_NAME);
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_emailotp() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Matches only valid and allowed email as username. Validates credentials
     * and password if exists in current session or generates ones for session
     * time on empty password treated as one-time password request.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $CFG, $DB;
        if (!validate_email($username) || email_is_not_allowed($username)) {
            return false;
        }
        // OTP already generated and base credentials matches.
        if (isset($_SESSION[self::COMPONENT_NAME]) &&
                $_SESSION[self::COMPONENT_NAME]['credentials'] === static::get_credentials($username)) {
            return empty($password)
                ? (bool) $this->redirect($username, notification::NOTIFY_INFO)
                : password_verify($password, $_SESSION[self::COMPONENT_NAME]['password']);
        }
        // OTP request - do not proceed on preventaccountcreation when user not exits.
        if (!isset($_SESSION[self::COMPONENT_NAME]) && empty($password) && (
                empty($CFG->authpreventaccountcreation) || $DB->get_field('user', 'id', [
                    'username'   => $username,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'auth'       => $this->authtype,
                    'deleted'    => 0,
                ]))) {
            $this->redirect($username, $this->gen_otp($username)
                ? notification::NOTIFY_SUCCESS
                : notification::NOTIFY_ERROR
            );
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function is_synchronised_with_external() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function is_internal() {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        // Destroy credentials - is already used.
        if (isset($_SESSION[self::COMPONENT_NAME])) {
            unset($_SESSION[self::COMPONENT_NAME]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get_userinfo($username) {
        $this->get_custom_user_profile_fields();
        $fields = array('email' => $username);
        if ($this->config->fieldsmapping_pattern &&
                $this->config->fieldsmapping_mapping) {
            $fields += array_filter(
                (new \auth_emailotp\fields_mapper(
                    $this->config->fieldsmapping_pattern,
                    strtolower($username),
                ))->map(array_map(function($mapping) {
                    return trim($mapping);
                }, explode(PHP_EOL, $this->config->fieldsmapping_mapping))),
                function($key) {
                    return in_array($key, $this->userfields) ||
                        in_array($key, $this->customfields);
                },
                ARRAY_FILTER_USE_KEY
            );
        }
        return $fields;
    }

    /**
     * get_credentials
     *
     * @param string $username
     * @return void
     */
    protected static function get_credentials($username) {
        return array(
            'username'   => $username,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'ip'         => getremoteaddr(),
        );
    }

    /**
     * gen_otp
     *
     * @param string $username
     * @return bool
     */
    protected function gen_otp(string $username) {
        global $CFG;
        $newpassword = generate_password();
        $_SESSION[self::COMPONENT_NAME] = array(
            'credentials' => static::get_credentials($username),
            'password'    => password_hash($newpassword, PASSWORD_DEFAULT),
        );
        $a = (object)array(
            'username' => $username,
            'password' => $newpassword,
        );
        return email_to_user(
        (object)array(
            'id'        => -1,
            'auth'      => $this->authtype,
            'username ' => $username,
            'email'     => $username,
        ),
        core_user::get_support_user(),
        sprintf(
            '%s: %s',
            format_string(get_site()->fullname),
            (string)new lang_string('otpgeneratedsubj', self::COMPONENT_NAME, $a, $CFG->lang)
        ),
        (string)new lang_string('otpgeneratedtext', self::COMPONENT_NAME, $a, $CFG->lang)
        );
    }

    /**
     * redirect
     *
     * @param string $username
     * @param string $msg
     * @return void
     */
    protected function redirect(string $username, string $msg) {
        global $CFG;
        redirect(
            get_login_url().'?username='.urlencode($username),
            (string)new lang_string('otpsent'.$msg, self::COMPONENT_NAME, null, $CFG->lang),
            null,
            $msg
        );
    }
}
