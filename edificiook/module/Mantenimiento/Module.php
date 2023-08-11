<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 11/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 23/03/2016.
 * Descripcion: Configuración del modulo Mantenimiento.
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Mantenimiento;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Mantenimiento\Model\EdificioTable;
use Mantenimiento\Model\DepartamentoTable;
use Mantenimiento\Model\ConceptoTable;
use Mantenimiento\Model\UsuarioTable;
use Mantenimiento\Model\ProveedorTable;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            /*'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ),
            ),*/
        );
    }

  

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {
        // You may not need to do this if you're doing it elsewhere in your
        // application
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $timeZone='America/Lima';
        date_default_timezone_set($timeZone);
    }

    public function getServiceConfig(){
        return array(
            'factories'=>array(
                'Mantenimiento\Model\EdificioTable' => function ($serviceManager)
                {
                    return new EdificioTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Mantenimiento\Model\DepartamentoTable' => function ($serviceManager)
                {
                    return new DepartamentoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Mantenimiento\Model\ConceptoTable' => function ($serviceManager)
                {
                    return new ConceptoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Mantenimiento\Model\UsuarioTable' => function ($serviceManager)
                {
                    return new UsuarioTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Mantenimiento\Model\ProveedorTable' => function ($serviceManager)
                {
                    return new ProveedorTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
            ),
        );
    }
}
