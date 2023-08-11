<?php

namespace Mantenimiento\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

class Edificio extends TableGateway
{
    public function __construct(Adapter $adapter = null, $databaseSchema = null, ResultSet $selectResultPrototype = null)
    {
        return parent::__construct('entidad_inmobiliaria', $adapter, $databaseSchema,$selectResultPrototype);
    }

	public function listarEdificio()
	{
	    $datos = $this->select();
	    return $datos->toArray();
	}
	public function listarEdificioPorId($id)
    {
        $id  = (int) $id;
        $rowset = $this->select(array('eni_in11_cod' => $id));
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("No hay registros asociados al valor $eni_in11_cod");
        }
        
        return $row;
    }

}

