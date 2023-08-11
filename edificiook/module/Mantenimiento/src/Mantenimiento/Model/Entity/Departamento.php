<?php

namespace Mantenimiento\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;

class Departamento extends TableGateway
{
    public function __construct(Adapter $adapter = null, $databaseSchema = null, ResultSet $selectResultPrototype = null)
    {
        return parent::__construct('departamento', $adapter, $databaseSchema,$selectResultPrototype);
    }

	public function listarDepartamento()
	{
	    $datos = $this->select();
	    return $datos->toArray();
	}

	public function listarUbigeo($params)
	{
		$page = $params['page']; // get the requested page
	    $limit= $params['rows']; // get how many rows we want to have into the grid
	    $sidx = $params['sidx']; // get index row - i.e. user click to sort
	    $sord = $params['sord']; // get the direction

	    $edificioId=$params['edificioId'];
	    $filtro=$params['filtro'];

        $adapter=$this->getAdapter();
	    $sql=new Sql($adapter);
		
        $select = $sql->select()
			->from(array('unidad' => $this->table),array('uni_in11_cod'))
            ->columns(array('uni_in11_cod','uni_vc150_tip','uni_vc20_dep','uni_do_aocu','uni_do_cm2','uni_do_por'))
			->join(array('user'=>'usuario'),'user.usu_in11_cod=unidad.uni_ch8_pos',array('residente'=>new Expression("CONCAT(usu_vc150_nom,' ',usu_vc50_ape)"),'usu_vc50_ema'))
            ->where(array('eni_in11_cod'=>$edificioId,"uni_in11_est"=>1,'uni_ch8_pad'=>''));

	     	$responce=array();
	     	$responce['page'] = 1;
            $responce['total'] = 1;
            $responce['records'] = 1;
			

            $selectString=$sql->buildSqlString($select);
            $edificioDetalle=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
            
            $i=0;
            foreach($edificioDetalle as $item)
            {   
                $querySubUnidades="SELECT count(*) as total from unidad_inmobiliaria where uni_ch8_pad='".$item['uni_in11_cod']."'";
                $nroSubUnidades=$adapter->query($querySubUnidades,$adapter::QUERY_MODE_EXECUTE)->toArray();

                $subUnidades=$nroSubUnidades[0]['total'];

                $responce['rows'][$i]['id'] = $item['uni_in11_cod'];

                if($subUnidades>0){
                    $responce['rows'][$i]['cell'] = array('','',
                                                            $item['uni_vc150_tip'].' '.$item['uni_vc20_dep'], 
                                                            $item['residente'],
                                                            $item['usu_vc50_ema'],
                                                            $item['uni_do_aocu'],
                                                            $item['uni_do_cm2'],
                                                            $item['uni_do_por'],
                                                            '1',
                                                            $item['uni_in11_cod'],
                                                            false,
                                                            true,
                                                            false,
                                                        );
                }else{
                    $responce['rows'][$i]['cell'] = array('','',
                                                            $item['uni_vc150_tip'].' '.$item['uni_vc20_dep'], 
                                                            $item['residente'],
                                                            $item['usu_vc50_ema'],
                                                            $item['uni_do_aocu'],
                                                            $item['uni_do_cm2'],
                                                            $item['uni_do_por'],
                                                            '0',
                                                            $item['uni_in11_cod'],
                                                            false,
                                                            true,
                                                            false,
                                                        );

                }

                $i++;
            }

            $i=8;
            $responce['rows'][$i]['id'] = '98989';
            $response['rows'][$i]['cell']=array('','',"DEPARTAMENTO DEMO1","KARINA LAZO","klazoa@gmail.com","112.57","8.690000","8.69",1,3964,true,false,true,);

            return $responce;


	}

}

