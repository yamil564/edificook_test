<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Proceso\Controller\Index' => 'Proceso\Controller\IndexController',
            'Proceso\Controller\Cuota'=>'Proceso\Controller\CuotaController',
            'Proceso\Controller\Consumo'=>'Proceso\Controller\ConsumoController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home-proceso' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/proceso',
                    'defaults' => array(
                        'controller' => 'Proceso\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'proceso' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/proceso[/:controller[/:action]]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'proceso\Controller',
                         'controller' => 'Index',
                         'action'     => 'index',
                     ),
                 ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'proceso' => __DIR__ . '/../view',
        ),
    ),
);
