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
 * Fields mapper.
 *
 * @package    auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailotp;

defined('MOODLE_INTERNAL') || die();

/**
 * fields_mapper
 *
 * Example of usage:
 *
 *     (new fields_mapper(
 *        '#/?P<FIRST>[^\.]+)\.(?P<LAST>[^@]+)@(?P<COMPANY>[^\.]+).*#',
 *        'my.name@corp.com'
 *     ))->map([
 *        'firstname:FIRST:ucfirst',
 *        'lastname:LAST:ucfirst',
 *        'institution:COMPANY:strtoupper,
 *     ]);
 *
 * gives:
 *
 *     ['firstname' => 'My',  'lastname'  => 'Name', 'institution' => 'CORP']
 *
 * @package    auth_emailotp
 * @copyright  2020 Pawel Suwinski <psuw@wp.pl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fields_mapper {

    const FORMAT = '^(\w+)\W(.+)';

    protected $replacepairs  = null;
    protected $allowedfilters = ['ucwords', 'ucfirst', 'strtoupper'];

    /**
     * __construct
     *
     * @param string $pattern Named capturing groups regexp patttern
     * @param string $subject The string being translated
     * @return void
     */
    public function __construct(string $pattern, string $subject) {
        $errhandler = set_error_handler(function ($severity, $message) {
            if (error_reporting() & $severity) {
                throw new \RuntimeException($message, $severity);
            }
        });
        if (preg_match($pattern, $subject, $replacepairs)) {
            array_shift($replacepairs);
            $this->replacepairs = $replacepairs;
        }
        set_error_handler($errhandler);
    }

    /**
     * set_allowed_filters
     *
     * @param array $allowedfilters Unary string functions names
     * @throws InvalidArgumentException|ReflectionException
     * @return self
     */
    public function set_allowed_filters(array $allowedfilters) {
        foreach ($allowedfilters as $filter) {
            $reflection = new \ReflectionFunction($filter);
            if ($reflection->getNumberOfParameters() !== 1 ||
                    $reflection->getNumberOfParameters()[0]->getType()->getName() != 'string') {
                throw new \InvalidArgumentException('Expected unary string function as a filter!');
            }
        }
        $this->allowedfilters = $allowedfilters;
        return $this;
    }

    /**
     * map_fields
     *
     * @param array $mapping
     * @return array
     */
    public function map(array $mapping) {
        if (empty($this->replacepairs)) {
            return array();
        }
        $allowedfilters = !empty($this->allowedfilters)
            ? '('.implode('|', $this->allowedfilters).')'
            : '';
        $fields = array();
        foreach ($mapping as $map) {
            $matches = array();
            !empty($allowedfilters) &&
                preg_match('/'.self::FORMAT.'\W'.$allowedfilters.'$/', $map, $matches) ||
                preg_match('/'.self::FORMAT.'$/', $map, $matches);
            if (count($matches) < 3) {
                continue;
            }
            $value = strtr($matches[2], $this->replacepairs);
            if (isset($matches[3])) {
                $value = call_user_func($matches[3], $value);
            }
            $fields[$matches[1]] = $value;
        }
        return $fields;
    }
}
