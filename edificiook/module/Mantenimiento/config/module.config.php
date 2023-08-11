<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Mantenimiento\Controller\Index' => 'Mantenimiento\Controller\IndexController',
            'Mantenimiento\Controller\Edificio' => 'Mantenimiento\Controller\EdificioController',
            'Mantenimiento\Controller\Departamento' => 'Mantenimiento\Controller\DepartamentoController',
            'Mantenimiento\Controller\Usuario' => 'Mantenimiento\Controller\UsuarioController',
            'Mantenimiento\Controller\Concepto' => 'Mantenimiento\Controller\ConceptoController',
            'Mantenimiento\Controller\Proveedor' => 'Mantenimiento\Controller\ProveedorController',
            'Mantenimiento\Controller\Configuraciondereservas' => 'Mantenimiento\Controller\ConfiguraciondereservasController',
        ),
    ),
    'router' => array(
         'routes' => array(
            'edificio' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/edificio',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Mantenimiento\Controller',
                        'controller' => 'Edificio',
                        'action' => 'index'
                    )
                )
            ),
            'edificio' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/edificio[/:action]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'Mantenimiento\Controller',
                         'controller' => 'Edificio',
                         'action'     => 'index',
                     ),
                 ),
            ),
            'mantenimiento' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/mantenimiento[/:controller[/:action]]',
                     'constraints' => array(
                         'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                         'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         '__NAMESPACE__' => 'Mantenimiento\Controller',
                         'controller' => 'Index',
                         'action'     => 'index',
                     ),
                 ),
            ),
         ),
     ),

    'view_manager' => array(
        'template_path_stack' => array(
            'Mantenimiento' => __DIR__ . '/../view',
        ),
    ),
);
