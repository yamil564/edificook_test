<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Seguridad\Controller\Index' => 'Seguridad\Controller\IndexController',
            'Seguridad\Controller\Useraccess' => 'Seguridad\Controller\UseraccessController',
            'Seguridad\Controller\Auditoria' => 'Seguridad\Controller\AuditoriaController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'seguridad' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/seguridad[/:controller[/:action]]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'seguridad\Controller',
                         'controller' => 'Index',
                         'action'     => 'index',
                     ),
                 ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'seguridad' => __DIR__ . '/../view',
        ),
    ),
    
);
