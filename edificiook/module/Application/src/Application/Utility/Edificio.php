<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 10/03/2016.
 * ultima modificacion por:
 * Descripcion: Utilidad para optener los datos del edificio en el layout
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Application\Utility;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Edificio implements ServiceLocatorAwareInterface
{

    protected $serviceLocator;

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function listar($params)
    {   
        $where=array( "uedificio.usu_in_cod"=>$params['userId']);

        $edifioTable = $this->getServiceLocator()->get("EdificioTable");
        $edificios = $edifioTable->getEdificios($where, array());
        return $edificios;
    }
    

    private function __getListaEdificios($where,$columns){
        
    }

}
