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
    public $courses = array();
    public $orphaned_courses = array();
    public $orphaned_units = array();

    public $moodle_courses = array();

    public function __construct() {
        global $USER, $CFG;
        $this->context = get_context_instance(CONTEXT_USER, $USER->id);

        // Is ual_mis class loaded?
        if (class_exists('ual_mis')) {
            $mis = new ual_mis();

            $ual_username = $mis->get_ual_username($USER->username);

            // What units is this user enrolled on?
            $units = $mis->get_user_units($ual_username);

            // What courses is this user enrolled on?
            $courses = $mis->get_user_courses($ual_username);

            // Which programmes is this user enrolled on?
            $programmes = $mis->get_user_programmes($ual_username);

            // Is the user enrolled on any Moodle courses that aren't recorded in the IDM data?
            $this->moodle_courses = $mis->get_moodle_courses($USER->id, $ual_username);

            // Now make each course adopt a unit. Note that units could have more than one parent...
            $this->courses = $this->construct_view_tree($programmes, $courses, $units);
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
    private function construct_view_tree($programmes, $courses, $units) {
        global $USER;

        $result = array();

        if (class_exists('ual_mis')) {
            $mis = new ual_mis();

            // Create a reference array of programmes
            $reference_programmes = array();
            if(!empty($programmes)) {
                foreach($programmes as $programme) {
                    $programme_code = $programme->get_aos_code().$programme->get_aos_period().$programme->get_acad_period();
                    $programme->set_user_enrolled($mis->get_enrolled($USER->id, $programme->get_moodle_course_id()));
                    $reference_programmes[$programme_code] = $programme;

                    // Remove programme from the $moodle_courses array if necessary
                    $programme_moodle_id = $programme->get_moodle_course_id();
                    if(isset($this->moodle_courses[$programme_moodle_id])) {
                        unset($this->moodle_courses[$programme_moodle_id]);
                    }
                }
            }

            $orphaned_courses = array();

            // Create a reference array of courses - make each course adopt their child/children...
            $reference_courses = array();
            if(!empty($courses)) {
                foreach($courses as $course) {
                    $course_code = $course->get_aos_code().$course->get_aos_period().$course->get_acad_period();
                    $course->set_user_enrolled($mis->get_enrolled($USER->id, $course->get_moodle_course_id()));
                    $reference_courses[$course_code] = $course;

                    // Is this course an orphan?
                    $parent = $course->get_parent();
                    if( strlen($parent) === 0 ) {
                        $orphaned_courses[$course_code] = $course;
                    } else {
                        if(isset($reference_programmes[$course->get_parent()])) {
                            $parent = $reference_programmes[$course->get_parent()];
                            if(!empty($parent)) {
                                $parent->adopt_child($course);
                            } else {
                                $orphaned_courses[$course_code] = $course;
                            }
                        } else {
                            $orphaned_courses[$course_code] = $course;
                        }
                    }
                }
            }

            $orphaned_units = array();

            // Create a reference array of units
            $reference_units = array();
            if(!empty($units)) {
                foreach($units as $unit) {
                    $unit_code = $unit->get_aos_code().$unit->get_aos_period().$unit->get_acad_period();
                    $unit->set_user_enrolled($mis->get_enrolled($USER->id, $unit->get_moodle_course_id()));
                    $reference_units[$unit_code] = $unit;

                    // Is this unit an orphan?
                    $parent = $unit->get_parent();
                    if( strlen($parent) === 0 ) {
                        $orphaned_units[$unit_code] = $unit;
                    } else {
                        $unit_parent = $unit->get_parent();
                        if(isset($reference_courses[$unit_parent])) {
                            $parent = $reference_courses[$unit_parent];
                            if(!empty($parent)) {
                                $parent->adopt_child($unit);
                            } else {
                                $orphaned_units[$unit_code] = $unit;
                            }
                        } else {
                            $orphaned_units[$unit_code] = $unit;
                        }
                    }
                }
            }

            // Now we have a relationship between courses and programmes... BUT we need to include the 'Course (all years)' level in the tree...
            if(!empty($reference_programmes)) {
                foreach($reference_programmes as $reference_programme) {
                    $course_years = $reference_programme->get_children();
                    $new_courses = $this->get_years_from_courses($course_years);

                    // Some orphaned courses may be the 'Course (all years)' homepages. If they are then we need to delete them
                    foreach($orphaned_courses as $elementkey=>$orphaned_course) {
                        foreach($new_courses as $course) {
                            $orphaned_course_id = $orphaned_course->get_idnumber();
                            $course_id = $course->get_idnumber();
                            if(strcmp($orphaned_course_id, $course_id) == 0) {
                                $course->set_fullname($orphaned_course->get_fullname());
                                $course->set_shortname($orphaned_course->get_shortname());
                                $course->set_idnumber($orphaned_course->get_idnumber());
                                $course->set_moodle_course_id($orphaned_course->get_moodle_course_id());
                                $course->set_user_enrolled($orphaned_course->get_user_enrolled());
                                $course->set_visible($orphaned_course->get_visible());
                                if(isset($orphaned_courses[$elementkey])) {
                                    unset($orphaned_courses[$elementkey]);
                                }
                            }
                        }
                    }

                    if(!empty($new_courses)) {
                        $reference_programme->abandon_children();
                        foreach($new_courses as $new_course) {
                            // Programmes need to adopt the 'Course (all years)'
                            $reference_programme->adopt_child($new_course);

                            // Remove course (all years) from the $moodle_courses array if necessary
                            $all_years_moodle_id = $new_course->get_moodle_course_id();
                            if(isset($this->moodle_courses[$all_years_moodle_id])) {
                                unset($this->moodle_courses[$all_years_moodle_id]);
                            }
                        }
                    }
                }
            }

            // Construct an array of courses, including orphaned courses and orphaned units:
            foreach($reference_programmes as $reference_programme) {
                $result[] = $reference_programme;
            }

            // Add an array element for each orphaned course...
            foreach($orphaned_courses as $orphaned_course) {
                $result[] = $orphaned_course;
            }

            // Then add an array element for each orphaned unit...
            foreach($orphaned_units as $orphaned_unit) {
                $result[] = $orphaned_unit;
            }

            // Finally record details of orphaned courses and units in case we need to do something with them later...
            $this->orphaned_courses = $orphaned_courses;
            $this->orphaned_units = $orphaned_units;
        }

        return $result;
    }

    private function get_years_from_courses($course_years) {
        global $USER;

        $result = array();

        $grouped_courses = array();

        if(!empty($course_years)) {
            // Group courses by year

            foreach($course_years as $course_year) {
                $course_name = $course_year->get_aos_code().substr($course_year->get_aos_period(),0,2).$course_year->get_acad_period();

                $grouped_courses[$course_name][] = $course_year;
            }

            if(!empty($grouped_courses)) {
                foreach($grouped_courses as $course_year => $courses) {
                    // Make new course for 'Course (all years)' level - this information needs to come from the API but construct it manually for now...
                    $new_course = new ual_course(array('fullname' => $course_year, 'idnumber' => $course_year, 'type' => ual_course::COURSETYPE_ALLYEARS));
                    // Do we need to link to a Moodle course?
                    $moodle_course = $this->get_moodle_course($course_year);
                    if($moodle_course) {
                        $new_course->set_moodle_course_id($moodle_course->id);
                        $new_course->set_fullname($moodle_course->fullname);
                        $mis = new ual_mis();
                        $new_course->set_user_enrolled($mis->get_enrolled($USER->id, $moodle_course->id));
                        $new_course->set_visible($moodle_course->visible);
                    }

                    $result[] = $new_course;
                    foreach($courses as $course) {
                        $result[] = $course;
                    }
                }
            }
        }

        return ($result);
    }

    private function get_moodle_course($courseid) {
        global $DB;

        $select = 'shortname IN (\''.$courseid.'\')';

        // TODO this doesn't need to fetch a recordset as we're only expecting a single record.
        $moodle_course = $DB->get_record_select('course', $select);

        return $moodle_course;
    }


}