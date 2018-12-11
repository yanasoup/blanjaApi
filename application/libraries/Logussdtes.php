<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Logussdtes {


    function __construct() {
        //require_once dirname(__FILE__).'/../../main/php/Logger.php';
        require_once 'log4php/Logger.php';
    }
    /**
     *
     * @return LoggerRoot
     */
    public static function getLogger() {
        Logger::configure("/var/www/alfamvp/application/libraries/log4php/configlogussdtes.xml");
        return Logger::getRootLogger();
    }

    /**
     *
     * @return string
     */
    public static function getLoggerDirectory() {
        return "/var/www/alfamvp/application/logs";
    }

}
