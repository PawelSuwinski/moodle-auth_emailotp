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
 * Strings for component 'auth_emailotp', language 'en'.
 *
 * @package   auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Email OTP';
$string['otpgeneratedsubj'] = 'One-time password';
$string['otpgeneratedtext'] = 'One-time password for current session: {$a->password}';
$string['otpsentsuccess'] = 'One-time password was sent to given email.';
$string['otpsenterror'] = 'An error occurred while sending one-time password.';
$string['otpsentinfo'] = 'One-time password for current session was already generated and sent to email.';
$string['fieldsmapping'] = 'User profile fields mapping';
$string['fieldsmapping_pattern'] = 'Pattern';
$string['fieldsmapping_pattern_help'] = 'Capturing groups PCRE patttern.';
$string['fieldsmapping_mapping'] = 'Mapping';
$string['fieldsmapping_mapping_help'] = 'Mapping expressions.';
$string['fieldsmapping_help'] = <<<'EOT'
<p> Usage example:</p>

Pattern:<br />
<pre>
'#/?P<FIRST>[^\.]+)\.(?P<LAST>[^@]+)@(?P<COMPANY>[^\.]+).*#',
</pre>

Mapping:<br />
<pre>
firstname:FIRST:ucfirst
lastname:LAST:ucfirst
institution:COMPANY:strtoupper
</pre>

<p>maps <em>my.name@corp.com</em> to:</p>

firstname: My<br />
lastname: Name<br />
institution: CORP<br />

<p>Allowed modifiers: ucfirst, ucwords, strtoupper.</p>
EOT;
