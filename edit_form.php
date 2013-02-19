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
 * Main block file
 *
 * @package    block
 * @subpackage ual_mymoodle
 * @copyright  2012 University of London Computer Centre
 * @author     Ian Wild {@link http://moodle.org/user/view.php?id=325899}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing a UAL My Moodle block
 *
 * @copyright 2012 Ian Wild
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ual_mymoodle_edit_form extends block_edit_form {
    /**
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('title', 'block_ual_mymoodle'));
        $mform->setDefault('config_title', get_string('pluginname', 'block_ual_mymoodle'));
        $mform->setType('config_title', PARAM_MULTILANG);

        $mform->addElement('advcheckbox', 'config_showcode', get_string('showcode', 'block_ual_mymoodle'));
        $mform->setDefault('config_showcode', 0);

        $mform->addElement('advcheckbox', 'config_showmoodlecourses', get_string('showmoodlecourses', 'block_ual_mymoodle'));
        $mform->setDefault('config_showmoodlecourses', 0);
        
        $mform->addElement('advcheckbox', 'config_showhiddencourses', get_string('showhiddencourses', 'block_ual_mymoodle'));
        $mform->setDefault('config_showhiddencourses', 1);

        $options = array(
            block_ual_mymoodle::TRIM_RIGHT => get_string('trimmoderight', 'block_ual_mymoodle'),
            block_ual_mymoodle::TRIM_LEFT => get_string('trimmodeleft', 'block_ual_mymoodle'),
            block_ual_mymoodle::TRIM_CENTER => get_string('trimmodecentre', 'block_ual_mymoodle')
        );
        $mform->addElement('select', 'config_trimmode', get_string('trimmode', 'block_ual_mymoodle'), $options);
        $mform->setType('config_trimmode', PARAM_INT);

        $mform->addElement('text', 'config_trimlength', get_string('trimlength', 'block_ual_mymoodle'));
        $mform->setDefault('config_trimlength', 50);
        $mform->setType('config_trimlength', PARAM_INT);
    }
}