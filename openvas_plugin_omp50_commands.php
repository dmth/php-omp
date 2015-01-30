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
 * This file contains the commands which are required to communicate with openvas by the OMP 5 protocol.
 * It is not complete.
 */


/*
 * Generates the command necessary to return all targets
 * @Param $withTasks if set to false, the system does not query for tasks which are associated to a target.
 * @Return The Command to get Targets
 */

function cmd_getTargets($withTasks = false, $id = "") {
    
    
    $r = "<get_targets";
    if ($withTasks) {
        $r .= " tasks=\"1\"";
    } 
    if (!empty($id)) {
        $r .= " target_id=\"".$id."\"";
    }
    $r .= " />";
    return $r;
}

/*
 * Generates the command necessary to return all TASK
 * @Param $uid the uid of a special TASK
 * @Return The Command to get TASK
 */

function cmd_getTasks($uid="") {
    if (empty($uid)){
        return "<get_tasks />";
    }else {
        return "<get_tasks task_id=\"".$uid."\"/>";
    }
}
/*
 * Generates the command necessary to start a TASK
 * @Param $uid the uid of a  TASK
 * @Return The command to start the TASK
 */
function cmd_startTask($uid=""){
    if (!empty($uid)){
        return "<resume_or_start_task task_id=\"".$uid."\"/>";
    }
}

/*
 * Generates the command necessary to create a new TASK
 * @Param $IP The Task will have the tagets IP as a Name
 * @Param $targetid the ID of the taget this task will be associated to
 * @Return The Command to create a new TASK
 */
function cmd_createTask($ip, $targetid){
    global $omp_new_task_default_comment, 
            $omp_alertid,
            $omp_scanconfig;
    
    return 
    "<create_task>"
        . "<name>"
            .$ip
        . "</name>"
        . "<comment>"
            .$omp_new_task_default_comment
        . "</comment>"
        . "<config id=\"".$omp_scanconfig."\" />"
        . "<alert id=\"".$omp_alertid."\" />"
        . "<target id=\"".$targetid."\" />"
    . "</create_task>";
}

/*
 * Generates the command necessary to create a new TARGET
 * @Param $ip the IP of the Target
 * @Return The Command to create a new TARGET
 */
function cmd_createTarget($ip){
    global $omp_new_target_default_comment;
    global $omp_portlistid, $omp_alivetest;
    
    return 
    "<create_target>"
        . "<name>"
            .$ip
        . "</name>"
        . "<comment>"
            .$omp_new_target_default_comment
        . "</comment>"
        . "<hosts>"
            .$ip
        . "</hosts>"
        //. "<exclude_hosts></exclude_hosts><reverse_lookup_only></reverse_lookup_only><reverse_lookup_unify></reverse_lookup_unify>"
        . "<port_list id=\"".$omp_portlistid."\" />" //@todo
        . "<alive_tests>".$omp_alivetest."</alive_tests>"
    . "</create_target>";
}

/*
 * Generates the command necessary to get a REPORT
 * @Param $reportid the ID of a report
 * @param $formatid the ID of the dataformat the report should have
 * @Return The Command to get REPORTS
 */
function cmd_getReports($reportid="", $formatid=""){ 
    $r = "<get_reports";
    if (!empty($reportid)){
        $r .= " report_id=\"".$reportid."\"";
        
        if (!empty($formatid)){
        $r .= " format_id=\"".$formatid."\"";
        }
    }
    $r .=  " />";
    return $r;
}

/*
 * Function generates the authenticate command
 */

function cmd_Authenticate($user="", $password="") {
    
    global $omp_username, $omp_password;
    $u = (empty($user))     ? $omp_username : $user;
    $p = (empty($password)) ? $omp_password : $password;
    return "<authenticate><credentials><username>$u</username><password>$p</password></credentials></authenticate>";
}

/*
 * Generates a <command> Sequence which holds the authentication and a list of operations which shall be carried out by openvas
 * @param $commands the list of OMP commands
 * @return a <command> Sequence as String
 */

function cmd_Commands($commands) {
    $c = "<commands>";
    $c .= cmd_Authenticate();
    $c .= $commands;
    $c .= "</commands>";
    return $c;
}


