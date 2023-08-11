<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Otros\Controller\Index' => 'Otros\Controller\IndexController',
            'Otros\Controller\Noticia' => 'Otros\Controller\NoticiaController',
            'Otros\Controller\Mail' => 'Otros\Controller\MailController',
        ),
    ),
    'router' => array(
         'routes' => array(
             'otros' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/otros[/:controller[/:action]]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'Otros\Controller',
                         'controller' => 'Index',
                         'action'     => 'index',
                     ),
                 ),
             ),
         ),
     ),

    'view_manager' => array(
        'template_path_stack' => array(
            'Otros' => __DIR__ . '/../view',
        ),
    ),
);
