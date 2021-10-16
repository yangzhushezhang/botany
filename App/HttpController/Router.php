<?php


namespace App\HttpController;


use EasySwoole\Http\AbstractInterface\AbstractRouter;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
    {
        /*
          * eg path : /router/index.html  ; /router/ ;  /router
         */
        $routeCollector->get('/router', '/test');
        /*
         * eg path : /closure/index.html  ; /closure/ ;  /closure
         */


        /**
         * AccountNumberController.php
         */
        $routeCollector->post('/user/add_account_number', '/User/AccountNumberController/add_account_number');
        # updated_leWallet
        $routeCollector->post('/user/updated_leWallet', '/User/AccountNumberController/updated_leWallet');
        #get_account_numberInformation
        $routeCollector->post('/user/get_account_numberInformation', '/User/AccountNumberController/get_account_numberInformation');
        # update_sunflowers
        $routeCollector->post('/user/update_sunflowers', '/User/AccountNumberController/update_sunflowers');


        /**
         * FarmInformationController.php
         */
        #refresh_botany  刷新农场
        $routeCollector->post('/user/refresh_botany', '/User/FarmInformationController/refresh_botany');
        # get_farmInformation
        $routeCollector->post('/user/get_farmInformation', '/User/FarmInformationController/get_farmInformation');

        # get_farmAccountInformation
        $routeCollector->post('/user/get_farmAccountInformation', '/User/FarmInformationController/get_farmAccountInformation');


        /**
         * ToolsController.php
         */
        #refresh_tools
        $routeCollector->post('/user/refresh_tools', '/User/ToolsController/refresh_tools');
        #get_tools
        $routeCollector->post('/user/get_tools', '/User/ToolsController/get_tools');
        #getEnergy
        $routeCollector->post('/user/getEnergy', '/User/ToolsController/getEnergy');


        /**
         * LoginController.php
         */
        $routeCollector->post('/user/login', '/User/LoginController/login');
        #set_API_KEY
        $routeCollector->post('/user/set_API_KEY', '/User/LoginController/set_API_KEY');

        /**
         * LoggerController
         */
        #getLogger
        $routeCollector->post('/user/getLogger', '/User/LoggerController/getLogger');


        /**
         * TheWorldTreeController.php
         * 世界树
         */

        #TheWorldTree
        $routeCollector->post('/user/TheWorldTree', '/User/TheWorldTreeController/TheWorldTree');
        $routeCollector->get('/user/TheWorldTree', '/User/TheWorldTreeController/TheWorldTree');
        $routeCollector->post('/user/getTodayTheWorldTree', '/User/TheWorldTreeController/getTodayTheWorldTree');
        $routeCollector->post('/user/yesterdayGetOne', '/User/TheWorldTreeController/yesterdayGetOne');
        $routeCollector->post('/user/yesterdayWatering', '/User/TheWorldTreeController/yesterdayWatering');

        /**
         * SpecialSeedController
         */
        $routeCollector->post('/user/getWaitingToHatch', '/User/SpecialSeedController/getWaitingToHatch');
        #getDoingToHatch
        $routeCollector->post('/user/getDoingToHatch', '/User/SpecialSeedController/getDoingToHatch');
        #IfPlanted
        $routeCollector->post('/user/IfPlanted', '/User/SpecialSeedController/IfPlanted');

        $routeCollector->get('/closure', function (Request $request, Response $response) {
            $response->write('this is closure router');
            //不再进入控制器解析
            return false;
        });
    }
}