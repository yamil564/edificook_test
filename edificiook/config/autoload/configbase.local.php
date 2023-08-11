<?php

/*
 * Configuración base del proyecto.
 * */
return array(
    'configbase'=> array(
        'dominios'=> array(
            'default'=>array(
                'ruta_theme'=>'themes/default-theme.css',
                'logo_panel'=>'file/logo-empresa/edificiook_panel.png',
                'logo_login'=>'./file/logo-empresa/edificiook_login.png',
                'titulo_panel'=>'edificioOk-Sistema de gestión de edificios',
                'titulo_login'=>'Login :: Sistema de gestión de edificios',
            ),
            'sys.adinco.pe'=>array(
                'ruta_theme'=>'themes/adinco-theme.css',
                'logo_panel'=>'file/logo-empresa/adinco_login.jpg',
                'logo_login'=>'./file/logo-empresa/adinco_panel.png',
                'titulo_panel'=>'Adinco :: Sistema de gestión de edificios',
                'titulo_login'=>'Login :: Sistema de gestión de edificios',
            )
        ),
        'proyecto'=>array(
            'titulo'=>'edificioOk',
            'descripcion'=>'Sistema de administración de edificios',
            'timezone'=>'America/Lima',
            'lenguaje'=>'es',
            'raiz'=>'/'
        ),
        'documentos'=>array(
            'keySecurityForRoute'=>'skjhsdf8973893784',
        )
    )
);