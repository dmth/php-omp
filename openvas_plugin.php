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

/*
 * This function draws a Button.
 * The type of the button can be modified, as well as the buttonvalue
 */

function makeButton($typeOfButton, $buttonvalue, $buttontext = "Openvas_Plugin_Button") {
    print '<form action="" method="post" name="openvas_plugin_button">';
    switch ($typeOfButton) {
        case "ScanTarget":
            // If no Active Task exists for this IP
            print '<input type="hidden" name="openvas_targetip" value="' . $buttonvalue . '" />';
            print '<input type="hidden" name="openvas_operation" value="scantarget" />';
            //deactivate button if an active scan exists
            $inprogress = openvas_action_chain_task_active_for_ip($buttonvalue);
            if (!$inprogress) {
                print '<input type="submit" value="' . $buttontext . '" />';
            } else {
                //print '<input type="submit" disabled="disabled" value="' . $buttontext . '" />';
                print '<progress value="'.$inprogress.'" max="100" title="Scanne: '.$inprogress.'%" alt="Scanne: '.$inprogress.'%">'.$inprogress.'%</progress>';
            }
            break;
        case "GetReport":
            print '<input type="hidden" name="openvas_targetip" value="' . $buttonvalue . '" />';
            print '<input type="hidden" name="openvas_operation" value="getreport" />';
            $timestamp = true; //initialise a non empty timestamp variable
            $latestreport = openvas_actionchain_getlatestreport_for_ip($buttonvalue, $timestamp);
            if ($latestreport){
                if ($timestamp === TRUE){
                    print '<input type="submit" value="' . $buttontext . '"/>';
                }else{
                    print '<input type="submit" value="' . $buttontext . '" title="Report vom '.$timestamp.' herunterladen"/>';
                }
            }
        break;
    }
    print '</form>';
}

/*
 * Initialise the plugin and check if something was posted to it.
 */

function openvas_plugin_init() {
    //cycle through all available Methods, defined in the array above.
    $method = filter_input(INPUT_POST, "openvas_operation");

    switch ($method) {
        case "scantarget":
            $ip = filter_input(INPUT_POST, "openvas_targetip");
            openvas_action_chain_scantarget($ip);
            break;
        case "getreport":
            $ip = filter_input(INPUT_POST, "openvas_targetip");
            $reportid = openvas_actionchain_getlatestreport_for_ip($ip);
            $report = openvas_actionchain_getreport_pdf($reportid);
            return_as_file($report, $ip);
            break;
        default:
            //There is nothing to do...
            break;
    }
    
}

/*
 * Returns an Array as a File, to make it downloadable
 * @Param an array['data', 'mimetype', 'extension']
 */
function return_as_file($array, $ip){
    
    $data = base64_decode($array['data']);
    $length=strlen($data);
    header('Content-Description: File Transfer');
    header('Content-Type: '.$array['mimetype']);//<<<<
    header('Content-Disposition: attachment; filename=OpenVAS-Report_for_IP_'.$ip.'.'.$array['extension']);
    header('Content-Transfer-Encoding: BASE64');
    header('Content-Length: ' . $length);
    
    print $data;
    exit;
}

/**
 * This is a chain of openvas-actions which are required to scan an IP
 * @param type $ip an IP-Addres
 * @todo Verify is $ip is well-formed
 * @return true if new SCAN was started, False otherwise
 */
function openvas_action_chain_scantarget($ip) {
    //Get all TARGETS
    $targetids = openvas_get_targets_for_ip($ip);

    if (!empty($targetids)) {
        //At least one Target exists for this IP
        $taskids = array();
        foreach ($targetids as $targetid) {
            //Check if Tasks exist for this Target ID
            $tids = openvas_get_tasks_for_target($targetid);
            //write $tids to $taskids
            while (!empty($tids)) {
                array_push($taskids, array_pop($tids));
            }
        }

        //Cycle through all TaskIDs and determine if they are active
        if (!empty($taskids)) {
            foreach ($taskids as $tid) {
                //Is the Task for this ID already active?
                if (openvas_task_active($tid) != FALSE) {
                    return false; //A Task is already Running for this IP
                }
            }
            //No Active Task was found, start one arbitraty Task
            send_Commands(cmd_startTask($taskids[0]));
            //@todo vaildate response
            //@todo finde a more clever way to determine the task that is started.
            return true;
        } else {
            //No Tasks exist for this Target
            // create a new TASK using this TARGET
            $newtaskresponse = simplexml_load_string(send_Commands(cmd_createTask($ip, $targetid)));
            //@todo validate success
            //If successfull START this TASK
            send_Commands(cmd_startTask($newtaskresponse->create_task_response['id']));
            //@todo validate success
            return true;
        }
    } else {
        // NO existing Target was Found for the IP,
        // create a new TARGET with this IP
        $newTargetResponse = simplexml_load_string(send_Commands(cmd_createTarget($ip)));
        //@todo validate success
        $newtargetid = $newTargetResponse->create_target_response['id'];
        // create a new TASK using this TARGET
        $newtaskresponse = simplexml_load_string(send_Commands(cmd_createTask($ip, $newtargetid)));
        //@todo validate success
        //If successfull START this TASK
        send_Commands(cmd_startTask($newtaskresponse->create_task_response['id']));
        //@todo validate success
        return true;
    }
}

/*
 * Checks if an active task exist for an IP-Address
 * @param IP-Address
 * @return PROGRESS of TASK if an active Task exits, else False
 */

function openvas_action_chain_task_active_for_ip($ip) {
    $targets = openvas_get_targets_for_ip($ip);
    foreach ($targets as $target) {
        $tasks = openvas_get_tasks_for_target($target);
        foreach ($tasks as $task) {
            $p = openvas_task_active($task);
            if ($p) {
                return $p;
            }
        }
    }
    return FALSE;
}


/*
 * Returns the latest ReportID for an IP-Addresses TASK
 * @param $ip IP-Address
 * @param $timestamp this variable will be filled with the timestamp opf the latest report, the variable should be true when it is passed to this function
 * @return ReportID, else False
 */
function openvas_actionchain_getlatestreport_for_ip($ip, &$timestamp=null){
    $targets = openvas_get_targets_for_ip($ip);
    foreach ($targets as $target) {
        $tasks = openvas_get_tasks_for_target($target);
        foreach ($tasks as $task) {
            $r = openvas_get_latestreportid_for_task($task);
            if ($r) {
                if($timestamp){
                    $timestamp=openvas_getlatestreport_timestamp($task);
                }
                return $r;
            }
        }
    }
    return FALSE;
}

/*
 * Returns the latest ReportID for an IP-Addresses TASK
 * @param $reportid The ID of the Report which has to be returned
 * @return Report data as an array['data', 'mimetype', 'extension'], else empty
 */
function openvas_actionchain_getreport_pdf($reportid){
    
    $r = array();
    
    global $omp_pdf_report_id;
    
    if (!empty($reportid)){
        $reportresponse=simplexml_load_string(send_Commands(cmd_getReports($reportid, $omp_pdf_report_id)));
        $data = $reportresponse->get_reports_response->report;
        $filetype = $reportresponse->get_reports_response->report['content_type'];
        $fileext = $reportresponse->get_reports_response->report['extension'];
                       
        $r['data'] = "$data";
        $r['mimetype'] = "$filetype";
        $r['extension'] = "$fileext";
    }
    return $r;
}


/*
 * Returns the ID of the latest REPORT of a TASK
 * This is the ID of the last report that was _finished_
 * @param $taskid the ID of a TASK
 * @return the ID of the latest report of a TASK or null on error or report exists
 */
function openvas_get_latestreportid_for_task($taskid){
    $report = null;
    if(!empty($taskid)){
        $taskresponse=simplexml_load_string(send_Commands(cmd_getTasks($taskid)));
        $report = $taskresponse->get_tasks_response->task->last_report->report["id"];
    }
    return $report;
}

/*
 * Returns the timestamp of the latest REPORT of a TASK
 * This is the timestamp of the last report that was _finished_
 * @param $taskid the ID of a TASK
 * @return the timestamp of the latest report of a TASK or null on error or report exists
 */
function openvas_getlatestreport_timestamp($taskid) {
    $timestamp = null;
    if(!empty($taskid)){
        $taskresponse=simplexml_load_string(send_Commands(cmd_getTasks($taskid)));
        $timestamp = $taskresponse->get_tasks_response->task->last_report->report->timestamp;
    }
    return $timestamp;
}


/*
 * Checks if a Task is active
 * @param $taskid the ID of the task
 * @return false if not active or the percentage of completeness
 */

function openvas_task_active($taskid) {

    $taskresponse = simplexml_load_string(send_Commands(cmd_getTasks($taskid)));
    $status = $taskresponse->get_tasks_response->task->status;
    if (in_array($status, runningtaskstatuses())) {
        $progress = $taskresponse->get_tasks_response->task->progress;
        return "$progress";
    } else {
        return FALSE;
    }
}

/*
 * Checks if a Task exists for a Target
 * @param $targetID the ID of a Target
 * @return an array of IDs of the tasks or null
 */

function openvas_get_tasks_for_target($targetid) {

    $r = array();

    $targetresponse = simplexml_load_string(send_Commands(cmd_getTargets(true, $targetid)));
    $tasks = $targetresponse->get_targets_response->target->tasks;
    foreach ($tasks->children() as $task) {
        $taskid = $task['id'];
        array_push($r, "$taskid");
    }
    return $r;
}

/*
 * Checks if a Target exists for an IP
 * @param $targetID the ID of a Target
 * @return an array of IDs with Targets which are associated to the IP, or empty array
 */

function openvas_get_targets_for_ip($ip) {
    //Get all TARGETS
    $targets = send_Commands(cmd_getTargets(false));
    $response = simplexml_load_string($targets);
    $r = array();
   
    //Evaluate if the IP is already Target.
    foreach ($response->get_targets_response->target as $target) {
        // The HOSTS field my contain many addresses, 
        // ATTENTION this does not check if an IP address is a member of a Network,
        // e.g. 192.168.0.1 is not found when 192.168.0.0/24 is stored in the HOSTS field
        // @todo
        $hosts = explode(',', $target->hosts);
        $targetid = $target['id'];
        foreach ($hosts as $host) {
            if ($ip == trim($host)) {
                array_push($r, "$targetid");
            }
        }
    }
    return $r;
}

