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

namespace Ue;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Ue\Model\CuotasTable;
use Ue\Model\NoticiasTable;
use Ue\Model\VisitasTable;
use Ue\Model\IngresoTable;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/' , __NAMESPACE__),
                ),
            ),
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
                'Ue\Model\CuotasTable' => function ($serviceManager)
                {
                    return new CuotasTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Ue\Model\NoticiasTable' => function ($serviceManager)
                {
                    return new NoticiasTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Ue\Model\VisitasTable' => function ($serviceManager)
                {
                    return new VisitasTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Ue\Model\IngresoTable' => function ($serviceManager)
                {
                    return new IngresoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
            ),
        );
    }
}
