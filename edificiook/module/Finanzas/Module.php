<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 23/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 12/04/2016.
 * Descripcion: Configuración del modulo Finanzas
 *
 * @author    J. Fidel Thompson Fonseca.
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */
namespace Finanzas;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Finanzas\Model\PresupuestoTable;
use Finanzas\Model\IngresoTable;
use Finanzas\Model\EgresoTable;
use Finanzas\Model\ImportaringresoTable;
use Finanzas\Model\ConciliacionbancariaTable;
use Finanzas\Model\SaldobancoTable;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }
    
    public function getServiceConfig(){
        return array(
            'factories'=>array(
                'Finanzas\Model\PresupuestoTable'=>function($serviceManager){
                    return new PresupuestoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Finanzas\Model\IngresoTable'=>function($serviceManager){
                    return new IngresoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Finanzas\Model\EgresoTable'=>function($serviceManager){
                    return new EgresoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Finanzas\Model\ImportaringresoTable'=>function($serviceManager){
                    return new ImportaringresoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Finanzas\Model\ConciliacionbancariaTable'=>function($serviceManager){
                    return new ConciliacionbancariaTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
                'Finanzas\Model\SaldobancoTable'=>function($serviceManager){
                    return new SaldobancoTable($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
            )
        );
    }
}
