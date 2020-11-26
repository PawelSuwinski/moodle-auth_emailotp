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
 * @see self::get_user_field()
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
 * @see self::get_user_field()
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
     * Matches only valid email from allowed domains. Validates credentials and
     * password if exists in current session or generates ones for session time
     * on empty password treated as one-time password request.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $CFG, $DB;
        $email = validate_email($username)
            ? $username // Email as username or signup on first login.
            : $this->get_user_field($username, 'email'); // Standard login, existing user.
        if (empty($email) || email_is_not_allowed($email)) {
            return false;
        }
        // OTP already generated and base credentials matches.
        if (isset($_SESSION[self::COMPONENT_NAME]) &&
                $_SESSION[self::COMPONENT_NAME]['credentials'] === static::get_credentials($username)) {
            if (empty($password)) {
                return (bool) $this->redirect($username, 'otpsent', notification::NOTIFY_INFO);
            } else if (password_verify($password, $_SESSION[self::COMPONENT_NAME]['password'])) {
                return true;
            }
        }
        // OTP request - do not proceed on preventaccountcreation when user not exits.
        if (!isset($_SESSION[self::COMPONENT_NAME]) && empty($password) && (
                empty($CFG->authpreventaccountcreation) || $this->get_user_field($username, 'id'))) {
            if (!$this->min_request_period_fulfilled($email)) {
                $this->redirect($username, 'otpperiod', notification::NOTIFY_WARNING);
            } else if ($this->gen_otp($username, $email)) {
                \auth_emailotp\event\otp_generated::create(array(
                    'other' => array('email' => $email),
                ))->trigger();
                $this->redirect($username, 'otpsent', notification::NOTIFY_SUCCESS);
            } else {
                $this->redirect($username, 'otpsent', notification::NOTIFY_ERROR);
            }
        }
        // OTP exits but validation failed - reset if revoke threshold is set.
        if (isset($_SESSION[self::COMPONENT_NAME])) {
            $_SESSION[self::COMPONENT_NAME]['login_failed_count']++;
            if (!empty($this->config->revokethreshold) &&
                    $_SESSION[self::COMPONENT_NAME]['login_failed_count'] >= $this->config->revokethreshold) {
                unset($_SESSION[self::COMPONENT_NAME]);
                \core\notification::add(get_string('otprevoked', self::COMPONENT_NAME),
                    notification::NOTIFY_WARNING
                );
                \auth_emailotp\event\otp_revoked::create(array(
                    'other' => array('email' => $email),
                ))->trigger();
            }
        }
        return false;
    }

    public function is_synchronised_with_external() {
        return false;
    }

    public function is_internal() {
        return false;
    }

    public function can_be_manually_set() {
        return true;
    }

    public function user_authenticated_hook(&$user, $username, $password) {
        // Destroy credentials - is already used.
        if (isset($_SESSION[self::COMPONENT_NAME])) {
            unset($_SESSION[self::COMPONENT_NAME]);
        }
    }

    /**
     * get_userinfo
     *
     * Signup and user creation on first login takes place only in case of
     * using email address as username.
     *
     * @param string $username
     * @return array
     */
    public function get_userinfo($username) {
        $this->get_custom_user_profile_fields();
        $fields = array('email' => $username);
        if ($this->config->fieldsmapping_pattern &&
                $this->config->fieldsmapping_mapping) {
            $fields += array_filter(
                (new \auth_emailotp\fields_mapper(
                    $this->config->fieldsmapping_pattern,
                    strtolower($username)
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
     * @param string $email
     * @return bool
     */
    protected function gen_otp(string $username, string $email) {
        global $CFG;
        $newpassword = generate_password();
        $_SESSION[self::COMPONENT_NAME] = array(
            'credentials' => static::get_credentials($username),
            'password'    => password_hash($newpassword, PASSWORD_DEFAULT),
            'login_failed_count' => 0,
        );
        $user = (object)array(
            'id'       => -1, // Fake due email_to_user() requirements.
            'auth'     => $this->authtype,
            'username' => $username,
            'email'    => $email,
            'password' => $newpassword,
        );
        return email_to_user($user, core_user::get_support_user(),
            format_string(get_site()->fullname).': '.
                get_string('otpgeneratedsubj', self::COMPONENT_NAME, $user),
            get_string('otpgeneratedtext', self::COMPONENT_NAME, $user)
        );
    }

    /**
     * redirect
     *
     * @param string $username
     * @param string $msg
     * @return void
     */
    protected function redirect(string $username, string $msg, string $level) {
        global $CFG;
        redirect(get_login_url().'?username='.urlencode($username),
            get_string($msg.$level, self::COMPONENT_NAME), null, $level);
    }

    /**
     * min_request_period_fulfilled
     *
     * @param string $email
     * @return bool
     */
    protected function min_request_period_fulfilled(string $email) {
        // Min request period security disabled.
        if (empty($this->config->minrequestperiod)) {
            return true;
        }
        // Log reader required - silently return failure on absence.
        $readers = get_log_manager()->get_readers('\core\log\sql_reader');
        $reader = reset($readers);
        return $reader && $reader->get_events_select_count(
            'component = ? AND action = ? AND timecreated >= ? AND other = ?',
            array(
                self::COMPONENT_NAME,
                'generated',
                time() - $this->config->minrequestperiod,
                json_encode(['email' => $email]),
            )
        ) === 0;
    }

    /**
     * get_user_field
     *
     * @see moodle_database::get_field()
     * @param string $username
     * @param string $field
     * @return mixed
     */
    protected function get_user_field(string $username, string $field) {
        global $CFG, $DB;
        return $DB->get_field('user', $field, array(
            'username'   => $username,
            'mnethostid' => $CFG->mnet_localhost_id,
            'auth'       => $this->authtype,
            'deleted'    => 0,
        ));
    }
}
