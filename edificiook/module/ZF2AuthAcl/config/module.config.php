<?php
return array(
    'router' => array(
        'routes' => array(
            'videotutorial' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/videotutorial',
                    'defaults' => array(
                        '__NAMESPACE__' => 'ZF2AuthAcl\Controller',
                        'controller' => 'Index',
                        'action'     => 'videotutorial',
                    ),
                ),
            ),
            'login' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/login',
                    'defaults' => array(
                        '__NAMESPACE__' => 'ZF2AuthAcl\Controller',
                        'controller' => 'Index',
                        'action' => 'index'
                    )
                )
            ),
            'logout' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/logout',
                    'defaults' => array(
                        '__NAMESPACE__' => 'ZF2AuthAcl\Controller',
                        'controller' => 'Index',
                        'action' => 'logout'
                    )
                )
            ),
            'browser' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/browser',
                    'defaults' => array(
                        '__NAMESPACE__' => 'ZF2AuthAcl\Controller',
                        'controller' => 'Index',
                        'action' => 'browser'
                    )
                )
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'ZF2AuthAcl\Controller\Index' => 'ZF2AuthAcl\Controller\IndexController'
        )
    ),
    'view_manager' => array(
        'template_map' => array(
            'layout/tutoriales'           => __DIR__ . '/../view/layout/tutoriales.phtml',
            'layout/auth' => __DIR__ . '/../view/layout/auth.phtml',
            'layout/browser' => __DIR__ . '/../view/layout/browser.phtml'
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view'
        )
    )
);


/*return array(
    'controllers' => array(
        'invokables' => array(
            'ZF2AuthAcl\Controller\Index' => 'ZF2AuthAcl\Controller\IndexController',
        ),
    ),
    'router' => array(
         'routes' => array(
             'zf2-auth-acl' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/zf2-auth-acl/index[/:action]',
                     'constraints' => array(
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
        'template_map' => array(
            'layout/auth' => __DIR__ . '/../view/layout/auth.phtml'
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view'
        )
    ),
);
*/