<?php
namespace ZF2AuthAcl\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use ZF2AuthAcl\Form\LoginForm;
use ZF2AuthAcl\Form\Filter\LoginFilter;
use ZF2AuthAcl\Utility\UserPassword;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;

class IndexController extends AbstractActionController
{

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function indexAction()
    {   
        $layout=$this->layout();
        $layout->setTemplate('layout/auth');
        $request = $this->getRequest();
        
        $view = new ViewModel();

        $loginForm = new LoginForm('loginForm');
        $loginForm->setInputFilter(new LoginFilter());
        
        if ($request->isPost()){
            $data = $request->getPost();
            $loginForm->setData($data);
            
            if ($loginForm->isValid()){
                $data = $loginForm->getData();
                
                $userPassword = new UserPassword();
                $encyptPass = $userPassword->create($data['password']);
                
                $authService = $this->getServiceLocator()->get('AuthService');
                
                $authService->getAdapter()
                    ->setIdentity($data['username'])
                    ->setCredential($encyptPass);
                
                $result = $authService->authenticate();
                
                if ($result->isValid()){
                    $params=array("username"=>$data['username']);
                    $userDetails = $this->_getUserDetails($params);

                    $session = new Container('User');
                    $session->offsetSet('username', $data['username']);
                    $session->offsetSet('userId', $userDetails[0]['usu_in_cod']);
                    $session->offsetSet('empId', $userDetails[0]['emp_in_cod']);
                    $session->offsetSet('tipoPerfil', $userDetails[0]['usu_ch_tipoperfil']);
                    $session->offsetSet('edificioId', 0);
                    /////////save auditoria////////
                    $params['idUsuario']=$userDetails[0]['usu_in_cod'];
                    $params['idEdificio']=$userDetails[0]['primeredificio'];
                    $params['accion']='Iniciar Sesión';
                    $this->saveAuditoria($params);
                    ///////////////////////////////
                    /* Si no existe el indice de "primeredificio" significa que el usuario 
                       aún no tiene edificios  asociados. Es necesario cerrar la session. 
                    */
                    if(isset($userDetails[0]['primeredificio'])){
                        $session->offsetSet('edificioId', $userDetails[0]['primeredificio']);
                    }else{
                        return $this->redirect()->toRoute('logout');
                    }
                    
                    
                    $this->flashMessenger()->addMessage(array(
                        'success' => 'Login Success.'
                    ));
                    switch ($userDetails[0]['usu_ch_tipoperfil']){
                        case 'ue':
                            return $this->redirect()->toRoute('home-ue');
                            break;
                        case 'ua':
                            return $this->redirect()->toRoute('home');
                            break;
                        case 'ur':
                            return $this->redirect()->toRoute('home');
                            break;
                        default:
                            return $this->redirect()->toRoute('logout');
                            break;
                    }
                    return $this->redirect()->toRoute('home');
                    // Redirect to page after successful login
                } else {
                    $this->flashMessenger()->addMessage(array(
                        'error' => 'Datos de acceso incorrectos.'
                    ));
                    // Redirect to page after login failure
                }
                return $this->redirect()->toRoute('login');
                // Logic for login authentication
            } else {
                $errors = $loginForm->getMessages();
                // prx($errors);
            }
        }
        
        $view->setVariable('loginForm', $loginForm);
        //$this->setLayout('layout/auth');
        return $view;

    }

    public function logoutAction(){
        $authService = $this->getServiceLocator()->get('AuthService');
        //params
        $session=new Container('User');
        $idEdificio=$session->offsetGet('edificioId');
        $idUsuario=$session->offsetGet('userId');
        /////////save auditoria////////
        $params['idUsuario'] = ($idUsuario!='')?$idUsuario:'';
        $params['idEdificio'] = ($idEdificio!='')?$idEdificio:'';
        $params['accion']='Cerrar Sesión';
        $this->saveAuditoria($params);
        ///////////////////////////////

        $session = new Container('User');
        $session->getManager()->destroy();
        
        $authService->clearIdentity();
        return $this->redirect()->tourl('./login');
    }

    public function recoverAction(){
        $layout=$this->layout();
        $layout->setTemplate('layout/auth');
        return new ViewModel();
    }

    public function browserAction(){
        $layout=$this->layout();
        $layout->setTemplate('layout/browser');
        return new ViewModel();
    }

    public function videotutorialAction(){
        $layout = $this->layout();
        $layout->setTemplate('layout/tutoriales');
        return new ViewModel();
    }
    
    private function _getUserDetails($params)
    {
        $userTable = $this->getServiceLocator()->get("UserTable");
        $users = $userTable->getUsers($params);
        return $users;
    }

    private function saveAuditoria($params)
    {   
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['idUsuario'], //idusuario
            'edi_in_cod'=> $params['idEdificio'], //idedificio
            'aud_opcion'=> 'Dashboard', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> '', //tabla
            'aud_in_numra'=> '', //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> '' //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

}