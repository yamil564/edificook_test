<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Theme extends AbstractHelper implements ServiceLocatorAwareInterface{
	public function __invoke(){

		$urlDominio=$_SERVER['HTTP_HOST'];
		$config=$this->getServiceLocator()->getServiceLocator()->get('Config');

		$configTheme=array();
		if(isset($config['configbase']['dominios'][$urlDominio])){
			$configTheme=$config['configbase']['dominios'][$urlDominio];
		}

		if(empty($configTheme ) ){
			$configTheme=$config['configbase']['dominios']['default'];
		}
		return $configTheme;
	}

	 /*
    * @return \Zend\ServiceManager\ServiceLocatorInterface
    */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;  
        return $this;  
    }
}