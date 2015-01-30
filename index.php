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

include_once 'openvas_plugin.php';

/*
    This script creates two buttons to use the OMP-Plugin
*/


try {
    openvas_plugin_init();
} catch (Exception $ex) {
    syslog(LOG_ERR, $ex->getMessage() . "possibly caused by: " . $ex->getPrevious());
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        try {
            makeButton("ScanTarget", "127.0.0.1", "Scan Target");
            makeButton("GetReport", "127.0.0.1", "Show Report");
        } catch (Exception $ex) {
            syslog(LOG_ERR, $ex->getMessage() . "possibly caused by: " . $ex->getPrevious());
        }
        ?>
    </body>
</html>

<?php
