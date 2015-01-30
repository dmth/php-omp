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
 * This file holds the functions which are required to communicate with the openvas-manager
 */

/*
 * Sends the commands to openvas
 * @param $commands the list of OMP commands
 * @return the Response of openvas
 */

function send_Commands($commands) {
    global $omp_host;
    global $omp_port;
    return _sendToOpenVAS(cmd_Commands($commands), $omp_host, $omp_port);
}

/*
 * Connects to OpenVAS-Manager and sends the configured XML
 * @return The Answer of OpenVas as a String or null in case of errors
 * @exception 
 * @todo Exception Handling
 * @todo Verfify if SSL/TLS Connection is handled properly and safe
 */

function _sendToOpenVAS($content, $host, $port) {

    /*
     * Verify if the content is valid,
     */
    try {
        $valid = _verifyOMP($content);
    } catch (Exception $ex) {
        throw new Exception("sendToOpenvas: I will not even try to connect to Openvas, as the content which shall be send is not valid.", null, $ex);
    }

    if (!$valid) {
        throw new Exception("sendToOpenvas: I will not even try to connect to Openvas, as the content which shall be send is not valid.");
    }

    /*
     * @todo Verify Host and Port
     */

    /*
     * Set Stream Context
     * @see http://php.net/manual/en/function.stream-context-create.php
     */
    $context = stream_context_create(array(
        'ssl' => array(
            'verify_peer' => false,
            'allow_self_signed' => true
        )
    ));

    // Response and Errors
    $response = null;
    $errno = null;
    $errstr = null;

    /*
     * Connect to OpenVAS with SSL/TLS
     * @todo does this work with TLS?
     */
    $fp = stream_socket_client('ssl://' . $host . ':' . $port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

    if ($errno) {
        throw new Exception("sendToOpenvas: The connection to openVAS failed, because of Error: (" . $errno . ") " . $errstr);
    }

    if ($fp) {
        try {
            /*
             * Send content to OpenVAS
             */
            fwrite($fp, $content);
            $response = _readStreamToBuffer($fp);
        } catch (Exception $ex) {
            throw new Exception("sendToOpenvas: The connection to openVAS failed, because of Error: (" . $errno . ") " . $errstr);
        }
    }

    return $response;
}

/* Read the Response into a buffer
 * @todo length of buffer? 
 */

function _readStreamToBuffer($fp, $length = 8192) {
    $response = "";
    do {
        $response.=$buf = fread($fp, $length);
    } while (strlen($buf) == $length);
    return $response;
}

/*
 * This function should verify if a given XML-String matches the OMP-Schema definition
 * @see http://openvas.org/protocol-doc.html
 * @return true if matches else if false
 * @exception throws exception if $omp is not a string
 * @todo this method is a simple stub, the verification against the schema has to be done,
 * right now it only checks whether $omp is a String
 */

function _verifyOMP($omp) {
    if (!is_String($omp)) {
        throw new Exception('OMP-Verification: Content is not a String.');
    }
    return true;
}