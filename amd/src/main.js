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
 * Javascript to initialise the block.
 *
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'],
    function ($) {

        var setnewpercent = function($div, i, top) {
            if (i <= top && top < 101) {
                $div.addClass('progressmeter_level-' + i);
                setTimeout(function() {
                    setnewpercent($div, i + 1, top);
                }, 50);
            }
        };

        /**
         * Initialise all for the block.
         *
         */
        var init = function () {

            $('.progressmeter_level').each(function() {
                var $this = $(this);
                var percent = $this.data('percent');

                if (percent) {
                    setnewpercent($this, 1, percent);
                }
            });
        };

        return {
            init: init
        };

    });
