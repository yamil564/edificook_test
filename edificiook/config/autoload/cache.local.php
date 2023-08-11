<?php

/**
*	Configuración chace REDIS
*
*/

return array(
	'caches'=> array(
		'redis-cache-service'=> array(
			'adapter'=> array(
				'name'=>'redis',
				'database'=>0,
				'options'=> array(
					'ttl'=>30,
					'server'=> array(
						'host'=>'127.0.0.1',
						'port'=>6379,
						'timeout'=>30
					)
				)
			),
			'plugins'=> array(
				'exception_handler'=>array(
					'throw_exceptions'=>false
				)
			)
		)
	)
);


/*return array(
    'redis' => array(
        'host' => '127.0.0.1',
        'port' => '6379',
        'timeout'=>30
    )
);*/