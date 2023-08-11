<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Session\Container;
use Application\Model\EdificioTable;
use Application\Utility\Edificio;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this,'onDispatchError'), -1);
        $eventManager->attach('dispatch',array($this,'loadConfiguracionLayout'));
            //$eventManager->attach("finish", array($this, "comprimirSalida"), 100);
        
    }

    public function comprimirSalida($e)
    {
        $response = $e->getResponse();
        $content = $response->getBody();
        //$content = preg_replace(array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '#(?://)?<![CDATA[(.*?)(?://)?]]>#s'), array('>', '<', '\\1', "//&lt;![CDATA[n".'1'."n//]]>"), $content);

        if (@strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            header('Content-Encoding: gzip');
            //$content = gzencode($content, 9);
        }
        $response->setContent($content);
    }

    function loadConfiguracionLayout(MvcEvent $event){

        $response=$event->getResponse();
        if($response->getStatusCode()==404){
            $viewModel =$event->getViewModel();
            $viewModel->setTemplate('error/404');
            return $viewModel;
        }
        
        $session = new Container('User');
        $controller=$event->getTarget();
        $controller->layout()->USERNAME_SESSION_ACTIVA=$session->offsetGet('username');

        $serviceManager=$event->getApplication()->getServiceManager();
        $edificio=$serviceManager->get('EdificioTable');

        $params=array(
            "empId"=>$session->offsetGet('empId'),
            "userId"=>$session->offsetGet('userId'),
            'edificioId'=>$session->offsetGet('edificioId'),
            'tipoPerfil'=>$session->offsetGet('tipoPerfil'),
        );

        $listaDeEdificios=$edificio->getEdificios($params);
        $listaMenu=$edificio->getMenu($params);

        $controller->layout()->EDIFICIOS=$listaDeEdificios;
        $controller->layout()->EDIFICIO_SELECCIONADO=$session->offsetGet('edificioId');
        $controller->layout()->MENU=$listaMenu;
        $controller->layout()->ROUTENAME=$event->getRouteMatch()->getMatchedRouteName();
        $controller->layout()->RAIZPROYECTO=$serviceManager->get('config')['configbase']['proyecto']['raiz'];

        $timeZone='America/Lima';
        date_default_timezone_set($timeZone);
    }

    function onDispatchError(MvcEvent $event){
        $serviceManager=$event->getApplication()->getServiceManager();
        $controller=$event->getTarget();
        $response=$event->getResponse();
        if($response->getStatusCode()==404){
            $viewModel =$event->getViewModel();
            $viewModel->setTemplate('error/404');
            return $viewModel;
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            /*'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),*/
        );
    }

    public function getServiceConfig(){
        return array(
            'factories'=>array(
                'Edificio'=>function($serviceManager){
                    return new Edificio();
                },
                'EdificioTable' => function ($serviceManager)
                {
                    return new EdificioTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
            ),
        );
    }
}
