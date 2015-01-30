<?php
/*
 *    Copyright (C) 2014-2015  Dustin Demuth
 *    Westfälische Wilhelms-Universität Münster
 *    Zentrum für Informationsverarbeitung - CERT
 *    
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 2 of the License, or
 *    (at your option) any later version.
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include_once 'openvas_plugin_config.php';
/**
 * This file holds Helper functions...
 * 
 */


/*
 * @return all possible taskstatuses as an array
 */
function taskstatuses(){
    return array("Delete Requested",
        "Done", 
        "New", 
        "Pause Requested", 
        "Paused", 
        "Requested", 
        "Resume Requested", 
        "Running", 
        "Stop Requested", 
        "Internal Error");
}

/*
 * @return all possible taskstatuses when a task is till running
 */
function runningtaskstatuses(){
    return array(
        "Running",
        "Requested"
        );
}


