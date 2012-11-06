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
 * This class creates a Programme, Course and Unit hierarchy. It shows the relationship between Moodle courses - which will be specific
 * to a given institution.
 */

// Fix bug: moving the block means the ual_api local plugin is no longer loaded. We'll need to specify the path to
// the lib file directly. See https://moodle.org/mod/forum/discuss.php?d=197997 for more information...
require_once($CFG->dirroot . '/local/ual_api/lib.php');

class course_hierarchy implements renderable {
    public $context;
    public $courses;
    public function __construct() {
        global $USER, $CFG;
        $this->context = get_context_instance(CONTEXT_USER, $USER->id);

        // Is ual_mis class loaded?
        if (class_exists('ual_mis')) {
            $mis = new ual_mis();

            $ual_username = $mis->get_ual_username($USER->username);

            // We require a tree of all programmes, courses and units for this user...

            $tree = $mis->get_user_complete($ual_username);

            $this->courses = $this->construct_view_tree($tree);
        }

        // TODO warn if local plugin 'ual_api' is not installed.
    }

    /**
     * We need to perform some jiggery-pokery here because the nesting of courses isn't quite right: each year in a
     * course has it's own course record in the database. These pages need to be collected together as 'Years'. Note
     * this is a slightly different arrangement from that shown on page 11 of the wireframe pack.
     *
     * @param $tree
     * @return mixed
     */
    private function construct_view_tree($tree) {
        // The $tree is an array. This function necessarily converts this into a multidimentional array...

        if (class_exists('ual_mis')) {
            $mis = new ual_mis();

            foreach($tree as $key=>$node) {
                $courses = $node->get_children();

                if(!empty($courses)) {

                    // Group courses by year...
                    $grouped_course_data = array();
                    foreach ($courses as $course) {
                        // TODO String functions are horribly inefficient so we might want to take a look at this.
                        $aos_period = $course->get_aos_period();

                        $unique_code = $course->get_unique_code();

                        if(strlen($aos_period) > 1) {
                            $year = intval(substr($aos_period, -2, 1));

                            $grouped_course_data[$unique_code][$year] = $course;
                        }
                    }

                    if(!empty($grouped_course_data)) {
                        // We are about to replace this node's children...
                        $node->abandon_children();

                        foreach($grouped_course_data as $code=>$years) {
                            // TODO We need to get the name (and link to Moodle course) from the 'course' table from the UAL api. Just use the name of the first year's homepage for now
                            $first_year = reset($years);
                            $coursepage = new ual_course(array('type' => ual_course::COURSETYPE_COURSE, 'shortname' => $first_year->get_shortname(), 'fullname' => $first_year->get_fullname(), 'id' => 0));

                            // TODO Courses may only run for 1 year. This would be indicated by the course name as described in the 'course' table.
                            foreach($years as $year) {
                                $aos_period = $year->get_aos_period();
                                if(strlen($aos_period) > 1) {
                                    $year_str = substr($aos_period, -2, 1);
                                } else {
                                    $year_str = get_string('unknown_year', 'block_ual_mymoodle');
                                }

                                // Use the UAL API to get the description of the year course from the MIS
                                $year_details = $mis->get_course_details($year->get_shortname());

                                if(!empty($year_details)) {
                                    $year->set_fullname($year_details['FULL_DESCRIPTION']);
                                    // TODO The following function needs to be called but at the moment I'm calling set_fullname() repeatedly within this loop...
                                    $coursepage->set_fullname($year_details['AOS_DESCRIPTION']);
                                } else {
                                    $year->set_fullname(get_string('year', 'block_ual_mymoodle').' '.$year_str);
                                }

                                $coursepage->adopt_child($year);
                            }

                            $node->adopt_child($coursepage);
                        }
                    }
                }
            }
        }

        return $tree;
    }
}