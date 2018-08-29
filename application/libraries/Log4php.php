<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Log4php {


    function __construct() {
        //require_once dirname(__FILE__).'/../../main/php/Logger.php';
        require_once 'log4php/Logger.php';
    }
    /**
     *
     * @return LoggerRoot
     */
    public static function getLogger() {
        Logger::configure("/var/www/blanjaApi/application/libraries/log4php/config.xml");
        return Logger::getRootLogger();
    }

    /**
     *
     * @return string
     */
    public static function getLoggerDirectory() {
        return "/var/www/blanjaApi/application/logs";
    }

}
