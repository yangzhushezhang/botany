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


        /**
         * FarmInformationController.php
         */
        #refresh_botany  刷新农场
        $routeCollector->post('/user/refresh_botany', '/User/FarmInformationController/refresh_botany');


        /**
         * ToolsController.php
         */
        #refresh_tools
        $routeCollector->post('/user/refresh_tools', '/User/ToolsController/refresh_tools');


        $routeCollector->get('/closure', function (Request $request, Response $response) {
            $response->write('this is closure router');
            //不再进入控制器解析
            return false;
        });
    }
}