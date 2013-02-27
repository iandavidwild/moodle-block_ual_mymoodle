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
 * Capabilities file
 *
 * @package    block
 * @subpackage ual_mymoodle
 * @copyright  2012-2013 University of London Computer Centre
 * @author     Ian Wild {@link http://moodle.org/user/view.php?id=81450}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$capabilities = array(

    'block/ual_mymoodle:can_edit' => array(
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array(
            'manager'          => CAP_ALLOW
        )
    ),
		
	'block/ual_mymoodle:show_hidden_courses' => array(
		'riskbitmask'  => RISK_PERSONAL,
		'captype'      => 'write',
		'contextlevel' => CONTEXT_SYSTEM,
		'archetypes'   => array(
			'manager'          => CAP_ALLOW
		)
	)
);