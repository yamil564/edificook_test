<?php

return array(
    'navigation'=>array(
        'default'=>array(
            array(
                'label'=>'Home',
                'route'=>'home',
                'class' => 'item-1', // with the help of ->setAddClassToListItem(true) the class will be moved to li or a tags
                'anchor_class' => "mmhome",
                'type' => 'Zend\Navigation\Page\Mvc',

            ),
            array(
                'label'=>'Mantenimiento',
                'route'=>'mantenimiento',
                'pages'=>array(
                    array(
                        'label'=>'Unidades',
                        'route'=>'mantenimiento',
                        'params' => array('controller' => 'departamento', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Proveedores',
                        'route'=>'mantenimiento',
                        'params' => array('controller' => 'proveedor', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Usuarios',
                        'route'=>'mantenimiento',
                        'params' => array('controller' => 'usuario', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Conceptos',
                        'route'=>'mantenimiento',
                        'params' => array('controller' => 'concepto', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Configuración de reservas',
                        'route'=>'mantenimiento',
                        'params' => array('controller' => 'configuraciondereservas', 'action' => 'index'),
                        'action'=>'index'
                    )
                )
            ),
            array(
                'label'=>'Finanzas',
                'route'=>'finanzas',
                'pages'=>array(
                    array(
                        'label'=>'Ingreso',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'ingreso', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Egreso',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'egreso', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Egreso',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'egreso', 'action' => 'pendiente-de-aprobacion'),
                        'action'=>'pendiente-de-aprobacion'
                    ),
                    array(
                        'label'=>'Egreso',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'egreso', 'action' => 'pendiente-de-pago'),
                        'action'=>'pendiente-de-pago'
                    ),
                    array(
                        'label'=>'Egreso',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'egreso', 'action' => 'pagado'),
                        'action'=>'pagado'
                    ),
                    array(
                        'label'=>'Presupuesto',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'presupuesto', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Conciliación Bancaria',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'conciliacionbancaria', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Control Contable',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'controlcontable', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Saldo en bancos',
                        'route'=>'finanzas',
                        'params' => array('controller' => 'saldoenbancos', 'action' => 'index'),
                        'action'=>'index'
                    ),
                )
            ),
            array(
                'label'=>'Procesos',
                'route'=>'proceso',
                'pages'=>array(
                    array(
                        'label'=>'Generar Cuota',
                        'route'=>'proceso',
                        'params' => array('controller' => 'cuota', 'action' => 'generar'),
                        'action'=>'generar'
                    ),
                    array(
                        'label'=>'Consumo de agua',
                        'route'=>'proceso',
                        'params' => array('controller' => 'consumo', 'action' => 'agua'),
                        'action'=>'agua'
                    ),
                )
            ),
            array(
                'label'=>'Reportes',
                'route'=>'reporte',
                'pages'=>array(
                    array(
                        'label'=>'Morosos',
                        'route'=>'reporte',
                        'params' => array('controller' => 'morosos', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Estado de cuenta',
                        'route'=>'reporte',
                        'params' => array('controller' => 'estado-cuenta', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Balance',
                        'route'=>'reporte',
                        'params' => array('controller' => 'balance', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Visitas',
                        'route'=>'reporte',
                        'params' => array('controller' => 'visitas', 'action' => 'index'),
                        'action'=>'index'
                    ),
                )
            ),
            array(
                'label'=>'Seguridad',
                'route'=>'seguridad',
                'pages'=>array(
                    array(
                        'label'=>'Auditoria',
                        'route'=>'seguridad',
                        'params' => array('controller' => 'auditoria', 'action' => 'index'),
                        'action'=>'index'
                    ),
                )
            ),
            array(
                'label'=>'Otros',
                'route'=>'otros',
                'pages'=>array(
                    array(
                        'label'=>'Correo',
                        'route'=>'otros',
                        'params' => array('controller' => 'mail', 'action' => 'index'),
                        'action'=>'index'
                    ),
                    array(
                        'label'=>'Noticias',
                        'route'=>'otros',
                        'params' => array('controller' => 'noticia', 'action' => 'index'),
                        'action'=>'index'
                    ),
                )
            )

        )

    ),
    'service_manager' => array(
         'factories' => array(
             'navigation' => 'Zend\Navigation\Service\DefaultNavigationFactory',
             // 'secondary_navigation' => 'CsnNavigation\Navigation\Service\SecondaryNavigationFactory',
             //'secondary_navigation' => 'Csn\Zend\Navigation\Service\SecondaryNavigationFactory',
         ),
     ),
);