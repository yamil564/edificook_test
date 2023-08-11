<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Finanzas\Controller\Index' => 'Finanzas\Controller\IndexController',
            'Finanzas\Controller\Presupuesto' => 'Finanzas\Controller\PresupuestoController',
            'Finanzas\Controller\Ingreso' => 'Finanzas\Controller\IngresoController',
            'Finanzas\Controller\Egreso' => 'Finanzas\Controller\EgresoController',
            'Finanzas\Controller\Controlcontable' => 'Finanzas\Controller\ControlcontableController',
            'Finanzas\Controller\Conciliacionbancaria' => 'Finanzas\Controller\ConciliacionbancariaController',
            'Finanzas\Controller\Saldoenbancos' => 'Finanzas\Controller\SaldoenbancosController',
            'Finanzas\Controller\Crep' => 'Finanzas\Controller\CrepController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home-finanzas' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/finanzas',
                    'defaults' => array(
                        'controller' => 'Finanzas\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'finanzas' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/finanzas[/:controller[/:action]]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'finanzas\Controller',
                         'controller' => 'Index',
                         'action'     => 'index',
                     ),
                 ),
            ),
        ),
    ),
    'service_manager'=>array(
        'abstract_factories'=>array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory'
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'finanzas' => __DIR__ . '/../view',
        ),
    ),
    
);
