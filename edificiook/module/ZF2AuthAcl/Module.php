<?php
namespace ZF2AuthAcl;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Authentication\Adapter\DbTable as DbAuthAdapter;
use Zend\Session\Container;
use ZF2AuthAcl\Model\User;

use Zend\Authentication\AuthenticationService;

class Module
{

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, array( $this,'boforeDispatch'), 100);
    }

    function boforeDispatch(MvcEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $target = $event->getTarget();

        $whiteList = array(
            'ZF2AuthAcl\Controller\Index-index',
            'ZF2AuthAcl\Controller\Index-logout',
            'ZF2AuthAcl\Controller\Index-browser',
            'ZF2AuthAcl\Controller\Index-videotutorial',
        );
        
        $requestUri = $request->getRequestUri();
        $controller = $event->getRouteMatch()->getParam('controller');
        
        $action = $event->getRouteMatch()->getParam('action');
        
        $requestedResourse = $controller . "-" . $action;

        //echo $requestedResourse;
        
        $session = new Container('User');
        
        if ($session->offsetExists('username') || $requestedResourse=='reporte\Controller\EstadoCuenta-recibo-estandar' || $requestedResourse=='reporte\Controller\EstadoCuenta-recibo-estandar2' || $requestedResourse=='reporte\Controller\EstadoCuenta-recibo-impresion' || $requestedResourse=='reporte\Controller\Balance-template'){
            if ($requestedResourse == 'ZF2AuthAcl\Controller\Index-index' || in_array($requestedResourse, $whiteList)) {
                $url = '/';
                $response->setHeaders($response->getHeaders()->addHeaderLine('Location', $url));
                $response->setStatusCode(302);
            } else {
                $routeName=$event->getRouteMatch()->getMatchedRouteName();
                if($session->offsetGet('tipoPerfil')=='ue' and $routeName!='ue'){
                    if($event->getRouteMatch()->getParams()['action']!='change'){
                        $url = $event->getRouter()->assemble(array(),array('name' => 'ue'));
                        $response->setHeaders($response->getHeaders()->addHeaderLine('Location', $url));
                        $response->setStatusCode(302);
                        $response->sendHeaders();
                        //die('Permiso denegado.');
                    }
                }
                // validar acceso al ambiente interno y externo  de acuerdo al tipo de usuario.
               // $serviceManager = $event->getApplication()->getServiceManager();
               // $userRole = $session->offsetGet('roleName');
                
                //$acl = $serviceManager->get('Acl');
                //$acl->initAcl();
                
                //$status = $acl->isAccessAllowed($userRole, $controller, $action);
                //if (! $status) {
                    //die('Permission denied');
                //}
            }   
        } else {
            
            if ($requestedResourse !='ZF2AuthAcl\Controller\Index-index' && ! in_array($requestedResourse, $whiteList)) {
                $url = $event->getRouter()->assemble(array(),array('name' => 'login'));
                $response->setHeaders($response->getHeaders()->addHeaderLine('Location', $url));
                $response->setStatusCode(302);
            }
            $response->sendHeaders();
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            /*'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                )
            )*/
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'AuthService' => function ($serviceManager)
                {
                    $adapter = $serviceManager->get('Zend\Db\Adapter\Adapter');
                    $dbAuthAdapter = new DbAuthAdapter($adapter, 'usuario', 'usu_vc_usu', 'usu_vc_pas');

                    //Pasamos objeto (como referencia) para validar  por estado estado activo de usuario.
                    $select = $dbAuthAdapter->getDbSelect();
                    $select->where('usu_in_est = 1');

                    
                    $auth = new AuthenticationService();
                    $auth->setAdapter($dbAuthAdapter);
                    return $auth;
                },
                'UserTable' => function ($serviceManager)
                {
                    return new User($serviceManager->get('Zend\Db\Adapter\Adapter'));
                },
            )
        );
    }
}