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
 * Event when one-time password is generated.
 *
 * @package    auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailotp\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event when one-time password is generated.
 *
 * @package    auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class otp_generated extends \core\event\base {

    protected const CRUD = 'c';

    protected function init() {
        $this->data['crud'] = static::CRUD;
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
    }

    public static function get_name() {
        return get_string('eventotp'.substr(static::class, strrpos(static::class, '_') + 1),
            'auth_emailotp');
    }

    public function get_description() {
        return sprintf('Password %s for \'%s\'', $this->action,
            $this->other['email']);
    }

    protected function get_legacy_logdata() {
        return array(SITEID, 'auth_emailotp', $this->action, '',
            $this->other['email']);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['email'])) {
            throw new \coding_exception('The \'email\' value must be set in other.');
        }
    }

    public static function get_other_mapping() {
        return false;
    }
}
