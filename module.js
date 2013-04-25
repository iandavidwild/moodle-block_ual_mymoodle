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
 * Javascript for the UAL My Moodle block
 *
 * @package    block
 * @subpackage ual_mymoodle
 * @copyright  2012-13 University of London Computer Centre
 * @author     Ian Wild {@link http://moodle.org/user/view.php?id=325899}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.block_ual_mymoodle = {};

M.block_ual_mymoodle.init_tree = function(Y, expand_all, htmlid, current_url) {

    Y.use('yui2-treeview', function(Y) {

        // 1. Fix to bug UALMOODLE-58: look for &amp; entity in label and replace with &. This is to fix a bug in YUI TreeView
        // 2. Focus the current node
        var current_node = null;

        /**
         * Note that this function caches the class names in the title rather than using an HTML5 'data-*' attribute
         * as we can't rely on HTML5 support.
         *
         * @param parentEl
         */
        function preprocess(parentEl){
            // check all the anchor elements and store in the title any class names. These will be replaced by YUI so we need to
            // record them somewhere.
            var all = parentEl.getElementsByTagName('a');

            var c;

            for (var i = -1, l = all.length; ++i < l;) {
                var e = all[i];
                if(e.className) {
                    c = e.getAttribute("class");
                    var original_title = e.title;
                    e.title = "<<<<"+c+">>>>"+original_title;
                }
            }
        }

        function addClass(id,new_class){
            var i,n=0;

            new_class=new_class.split(",");

            for(i=0;i<new_class.length;i++){
                var el = document.getElementById(id);
                if(el != null) {
                    if((" "+el.className+" ").indexOf(" "+new_class[i]+" ")==-1){
                        el.className+=" "+new_class[i];
                    }
                    n++;
                }
            }

            return n;
        }

        function postprocess(parentEl){
            // replace any class names that were removed from anchors when the tree was built. See preprocess() for details.
            var all = parentEl.getElementsByTagName('a');

            for (var i = -1, l = all.length; ++i < l;) {
                var e = all[i];
                if(e.title) {
                    var title = e.title;
                    var start_of_list = title.indexOf('<<<<');
                    if(start_of_list > -1) {
                        start_of_list = start_of_list + 4; // offset to the end of the chevrons.
                        var end_of_list = title.indexOf('>>>>');
                        var class_list = title.substring(start_of_list, end_of_list);

                        var c = e.getAttribute("class");
                        c = c+" "+class_list;
                        e.className = c;

                        // Now change the title back
                        var new_title = title.substring(end_of_list+4, title.length);
                        e.title = new_title;
                    }//if(start_of_list > -1) {
                }//if(e.title) {
            }//for (var i = -1, l = all.length; ++i < l;) {
        }

        function tree_traversal(node){
            if(node.hasChildren){
                var nodes = node.children;
                for(var i = 0; i < nodes.length; i++)    {
                    var test_node = nodes[i];
                    var label = test_node.label;
                    if(label){
                        var decoded = label.replace(/&amp;/g, '&');
                        test_node.label = decoded;
                    }
                    if(current_url) {
                        var href = test_node.href;

                        if(href == current_url) {
                            current_node = test_node;
                        }
                    }
                    tree_traversal(test_node);
                }
            }
        }

        function onClickEvent(oArgs) {
            var node = oArgs.node;

            if(node.expanded) {
                node.collapse();
            } else {
                node.expand();
            }
        }

        // Preprocess elements before the tree is constructed
        var parentEl = document.getElementById(htmlid);
        preprocess(parentEl);

        // Construct the tree
        var tree = new YAHOO.widget.TreeView(htmlid);

        tree.subscribe("expandComplete", function(node) {
            var elid = node.getElId();

            var el = document.getElementById(elid);

            postprocess(el);
        });

        tree.subscribe("clickEvent", onClickEvent);

        var root = tree.getRoot();

        // Now the tree has been constructed traverse it to correct duff HTML.
        // tree_traversal() is a recursive function so pass it the global root node...
        tree_traversal(root);

        // The tree is not created in the DOM until this method is called:
        tree.render();

        // Post process the rendered tree - note not all of the nodes will be rendered at this point (they are
        // loaded but removed from the DOM). Again, postprocess is a recursive function...
        postprocess(parentEl);
        
        // Move focus to the current node...
        if(current_node) {
            current_node.focus();
        }
    });
};


