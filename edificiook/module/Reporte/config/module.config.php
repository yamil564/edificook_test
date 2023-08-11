<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Reporte\Controller\Index' => 'Reporte\Controller\IndexController',
            'Reporte\Controller\EstadoCuenta'=>'Reporte\Controller\EstadoCuentaController',
            'Reporte\Controller\Balance'=>'Reporte\Controller\BalanceController',
            'Reporte\Controller\Morosos'=>'Reporte\Controller\MorososController',
            'Reporte\Controller\Visitas'=>'Reporte\Controller\VisitasController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home-reporte' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/reporte',
                    'defaults' => array(
                        'controller' => 'Reporte\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'reporte' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/reporte[/:controller[/:action]]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'reporte\Controller',
                         'controller' => 'Index',
                         'action'     => 'index',
                     ),
                 ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'reporte' => __DIR__ . '/../view',
        ),
    ),
);
