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
 * Print course hierarchy
 *
 * @package    block_ual_mymoodle
 * @copyright  2012 University of London Computer Centre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/ual_mymoodle/lib.php');

class block_ual_mymoodle_renderer extends plugin_renderer_base {

    private $showcode = 0;
    private $trimmode = block_ual_mymoodle::TRIM_RIGHT;
    private $trimlength = 50;

    /**
     * Prints course hierarchy view
     * @return string
     */
    public function course_hierarchy($showcode, $trimmode, $trimlength) {
        $this->showcode = $showcode;
        $this->trimmode = $trimmode;
        $this->trimlength = $trimlength;

        return $this->render(new course_hierarchy);
    }

    /**
     * Provides the html contained in the course hierarchy/'My UAL Moodle' block. The hierarchy is passed as a tree.
     *
     * @param render_course_hierarchy $tree
     * @return string
     */
    public function render_course_hierarchy(course_hierarchy $tree) {
        if (empty($tree) ) {
            $html = $this->output->box(get_string('nocourses', 'block_ual_mymoodle'));
        } else {
            $htmlid = 'course_hierarchy_'.uniqid();
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree->courses);
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Converts the course hierarchy into something more meaningful.
     *
     * @param $tree
     * @param int $indent
     * @return string
     */
    protected function htmllize_tree($tree, $indent=0) {
        global $CFG;

        $result = '<ul>';

        if (empty($tree)) {
            $result .= html_writer::tag('li', get_string('nothingtodisplay'));
        } else {
            foreach ($tree as $node) {

                $name = $node->get_fullname();
                if($this->showcode == 1) {
                    $name .= ' ('.$node->get_shortname().')';
                }
                $course_fullname = $this->trim($name);
                $node_type = $node->get_type();
                $type_id = '';
                switch($node_type) {
                    case ual_course::COURSETYPE_PROGRAMME:
                        $type_id = 'programme';
                    case ual_course::COURSETYPE_COURSE:
                        $type_id = 'course';
                    case ual_course::COURSETYPE_UNIT:
                        $type_id = 'unit';
                }
                $attributes = array('id' => $type_id);

                if($node_type == ual_course::COURSETYPE_UNIT) {
                    // Create a link...
                    $attributes['title'] = $course_fullname;
                    $moodle_url = $CFG->wwwroot.'/course/view.php?id='.$node->get_id();
                    $content = html_writer::link($moodle_url, $course_fullname, $attributes);

                } else {
                    // Don't...
                    $content = html_writer::tag('strong', $course_fullname, $attributes);
                }

                $children = $node->get_children();


                if ($children == null) {
                    $result .= html_writer::tag('li', $content, $attributes);

                } else {
                    // If this has parents OR it doesn't have parents or children then we need to display it...???
                    $result .= html_writer::tag('li', $content.$this->htmllize_tree($children, $indent+1), $attributes);
                }
            }
        }
        $result .= '</ul>';

        return $result;
    }

    /**
     * Trims the text and shorttext properties of this node and optionally
     * all of its children.
     *
     * @param string $text The text to truncate
     * @return string
     */
    private function trim($text) {
        $result = $text;

        switch ($this->trimmode) {
            case block_ual_mymoodle::TRIM_RIGHT :
                if (textlib::strlen($text)>($this->trimlength+3)) {
                    // Truncate the text to $long characters.
                    $result = textlib::substr($text, 0, $this->trimlength).'...';
                }
                break;
            case block_ual_mymoodle::TRIM_LEFT :
                if (textlib::strlen($text)>($this->trimlength+3)) {
                    // Truncate the text to $long characters.
                    $result = '...'.textlib::substr($text, textlib::strlen($text)-$this->trimlength, $this->trimlength);
                }
                break;
            case block_ual_mymoodle::TRIM_CENTER :
                if (textlib::strlen($text)>($this->trimlength+3)) {
                    // Truncate the text to $long characters.
                    $length = ceil($this->trimlength/2);
                    $start = textlib::substr($text, 0, $length);
                    $end = textlib::substr($text, textlib::strlen($text)-$this->trimlength);
                    $result = $start.'...'.$end;
                }
                break;
        }
        return $result;
    }
}


