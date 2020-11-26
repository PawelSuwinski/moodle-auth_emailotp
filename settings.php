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
 * Admin settings and defaults

 * @package    auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading('auth_emailotp/pluginname',
        new lang_string('fieldsmapping', 'auth_emailotp'),
        new lang_string('fieldsmapping_help', 'auth_emailotp')));
    $settings->add(new class(
        'auth_emailotp/fieldsmapping_pattern',
        get_string('fieldsmapping_pattern', 'auth_emailotp'),
        get_string('fieldsmapping_pattern_help', 'auth_emailotp'),
        '', PARAM_RAW_TRIMMED
    ) extends admin_setting_configtext {
        public function validate($data) {
            if (true !== $result = parent::validate($data)) {
                return $result;
            }
            try {
                new \auth_emailotp\fields_mapper($data, '');
            } catch (\RuntimeException $e) {
                return $e->getMessage();
            }
                return true;
        }
    });

    $settings->add(new admin_setting_configtextarea('auth_emailotp/fieldsmapping_mapping',
        get_string('fieldsmapping_mapping', 'auth_emailotp'),
        get_string('fieldsmapping_mapping_help', 'auth_emailotp'), '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_heading('auth_emailotp/security',
        new lang_string('security', 'admin'), ''));

    $settings->add(new admin_setting_configtext('auth_emailotp/revokethreshold',
        get_string('revokethreshold', 'auth_emailotp'),
        get_string('revokethreshold_help', 'auth_emailotp'), 3, PARAM_INT));

    $settings->add(new class(
        'auth_emailotp/minrequestperiod',
        get_string('minrequestperiod', 'auth_emailotp'),
        get_string('minrequestperiod_help', 'auth_emailotp')
    ) extends admin_setting_configtext {
        public function __construct($name, $visiblename, $description) {
            $readers = get_log_manager()->get_readers('\core\log\sql_reader');
            $logreader = reset($readers);
            parent::__construct($name, $visiblename, $description, $logreader ? 120 : 0, PARAM_INT);
            if (!$logreader && !empty($this->get_setting())) {
                $this->description .= ' '.get_string('logstorerequired', 'auth_emailotp',
                    (string)new moodle_url('/admin/settings.php', ['section' => 'managelogging'])
                );
            }
        }
    });

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('emailotp');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
        get_string('auth_fieldlocks_help', 'auth'), false, false,
        $authplugin->get_custom_user_profile_fields());
}
