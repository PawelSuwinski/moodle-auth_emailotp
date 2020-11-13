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
 * Strings for component 'auth_emailotp', language 'pl'.
 *
 * @package   auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Email OTP';
$string['otpgeneratedsubj'] = 'Hasło jednorazowe';
$string['otpgeneratedtext'] = 'Hasło jednorazowe dla bieżącej sesji: {$a->password}';
$string['otpsentsuccess'] = 'Hasło jednorazowe zostało wysłane na podany adres email.';
$string['otpsenterror'] = 'Wystąpił błąd podczas wysyłania hasła jednorazowego.';
$string['otpsentinfo'] = 'Hasło jednorazowe dla bieżącej sesji już zostało wygenerowane i wyłane.';
$string['fieldsmapping'] = 'Mapowanie pól profilu użytkownika';
$string['fieldsmapping_pattern'] = 'Wzorzec';
$string['fieldsmapping_pattern_help'] = 'Grupujące wyrażenie regularne PCRE.';
$string['fieldsmapping_mapping'] = 'Mapowanie';
$string['fieldsmapping_mapping_help'] = 'Wyrażenie mapujące.';
$string['fieldsmapping_help'] = <<<'EOT'
<p> Przykład użycia:</p>

Wzorzec:<br />
<pre>
'#/?P<FIRST>[^\.]+)\.(?P<LAST>[^@]+)@(?P<COMPANY>[^\.]+).*#',
</pre>

Mapowanie:<br />
<pre>
firstname:FIRST:ucfirst
lastname:LAST:ucfirst
institution:COMPANY:strtoupper
</pre>

<p>odwzoruje <em>my.name@corp.com</em> na:</p>

firstname: My<br />
lastname: Name<br />
institution: CORP<br />

<p>Dozwolone modyfikatory: ucfirst, ucwords, strtoupper.</p>
EOT;
