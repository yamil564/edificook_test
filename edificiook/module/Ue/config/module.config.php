<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Ue\Controller\Index' => 'Ue\Controller\IndexController',
            'Ue\Controller\Cuotas' => 'Ue\Controller\CuotasController',
            'Ue\Controller\Noticias' => 'Ue\Controller\NoticiasController',
            'Ue\Controller\Visitas' => 'Ue\Controller\VisitasController',
            'Ue\Controller\Presupuesto' =>'Ue\Controller\PresupuestoController',
            'Ue\Controller\Balance' => 'Ue\Controller\BalanceController',
            'Ue\Controller\Morosos' => 'Ue\Controller\MorososController',
            'Ue\Controller\Ingreso' => 'Ue\Controller\IngresoController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home-ue' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/ue',
                    'defaults' => array(
                        'controller' => 'Ue\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'noticias' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/noticias',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Ue\Controller',
                        'controller' => 'Noticias',
                        'action' => 'index'
                    )
                )
            ),
            'ue' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/ue[/:controller[/:action]]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'ue\Controller',
                         'controller' => 'Index',
                         'action'     => 'index',
                     ),
                 ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'ue' => __DIR__ . '/../view',
        ),
    ),
);
