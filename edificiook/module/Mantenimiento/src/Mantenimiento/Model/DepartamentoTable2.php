<?php

/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 11/03/2016.
 * ultima modificacion por: Jhnon Gómez, Meler Carranza
 * Fecha Modificacion: 21/04/2016, 02-06-2016
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson 
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Mantenimiento\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class DepartamentoTable extends AbstractTableGateway{

	public $table="unidad";

	public function __construct(Adapter $adapter)
	{
		$this->adapter = $adapter;
	}

	public function getUnidadesPorEdificioParaGrid($params){
        try {
            $adapter=$this->getAdapter();
            $sql=new Sql($adapter);
        

            $page = $params['page']; // pagina actual
            $limit= $params['rows']; // numero de filas para mostrar en el grid
            $sidx = $params['sidx']; // jqgrid columna para ordenar
            $sord = $params['sord']; // orden
            $edificioId=$params['edificioId'];

            //filter
            $search = $this->buscarUnidad($params['_search'], $params['filters']);
            
            //si se recibe el parametro "n_level" significa que el se esta solicitado unidades secundarias.
            if(isset($params['nodeid'])){
                if($params['nodeid'] != ''){
                    
                    if(isset($params['n_level'])){
                        $nivel=$params['n_level'];
                        if($nivel==0){
                            $idUnidadPadre=$params['nodeid'];
                            $selectSubUnidades=$sql->select()
                            ->from(array('uni'=>$this->table))
                            ->columns(array('uni_in_cod','uni_vc_tip','uni_vc_nom','uni_do_aocu','uni_do_cm2','uni_do_pct'))
                            ->join(array('user'=>'usuario'),'user.usu_in_cod=uni.uni_in_pos',array('residente'=>new Expression("CONCAT(usu_vc_nom,' ',usu_vc_ape)"),'usu_vc_ema'),'left')
                            ->where(array("uni_in_est"=>1,'uni_in_pad'=>$idUnidadPadre));



                            $selectStringSubUnidades=$sql->buildSqlString($selectSubUnidades);
                            $subUnidades=$adapter->query($selectStringSubUnidades,$adapter::QUERY_MODE_EXECUTE)->toArray();

                            //$responce = array();
                            $i=0;
                            foreach($subUnidades as $item){   
                                $checkRow ="<center><label class='position-relative tooltipmsg'><input type='checkbox' id='chkCol". $item['uni_in_cod'] . "' name='chkCol". $item['uni_in_cod'] . "' class='ace' onclick='GridUnidad.checkBoxSelected(" . $item['uni_in_cod'] .");'/><span class='lbl'></span></label></center>";

                                $responce['rows'][$i]['id'] = $item['uni_in_cod'];
                                $responce['rows'][$i]['cell'] = array( 
                                    $checkRow,
                                    $item['uni_vc_tip'].' '.$item['uni_vc_nom'], 
                                    $item['residente'],
                                    $item['usu_vc_ema'],
                                    $item['uni_do_aocu'],
                                    $item['uni_do_cm2'],
                                    $item['uni_do_pct'],
                                    '1',
                                    $idUnidadPadre,
                                    true,
                                    false
                                );
                                $i++;
                            }
                            return $responce;
                        }
                    }

                } 
            }

            if($search!=''){

                $query = "SELECT user.usu_vc_ema, CONCAT(uni.uni_vc_tip,' ',uni.uni_vc_nom) AS unidad, CONCAT(user.usu_vc_nom,' ',user.usu_vc_ape) AS residente, uni.uni_in_cod, uni.uni_vc_tip, uni.uni_vc_nom, uni.uni_do_aocu, uni.uni_do_cm2, uni.uni_do_pct FROM unidad AS uni INNER JOIN usuario AS user ON user.usu_in_cod=uni.uni_in_pos WHERE uni.edi_in_cod = '$edificioId' AND uni.uni_in_est = 1 AND uni.uni_in_pad IS NULL $search ";
                //echo $query;
                $unidadesPadre = $adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->toArray();

            }else{
                $select = $sql->select()
                ->from(array('uni' => $this->table),array('uni_in_cod'))
                ->columns(array('uni_in_cod','uni_vc_tip','uni_vc_nom','uni_do_aocu','uni_do_cm2','uni_do_pct'))
                ->join(array('user'=>'usuario'),'user.usu_in_cod=uni.uni_in_pos',array('residente'=>new Expression("CONCAT(usu_vc_nom,' ',usu_vc_ape)"),'usu_vc_ema'),'left')
                ->where(array('edi_in_cod'=>$edificioId,"uni_in_est"=>1,'uni_in_pad'=>NULL));
                $selectString=$sql->buildSqlString($select);
                $unidadesPadre=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
            }

            $responce=array();

            $sql=new Sql($adapter);
            $selectUnidad=$sql->select()
                ->from($this->table)
                ->columns(array(
                    'totalAream2'=>new Expression('SUM(uni_do_aocu)'),
                    'totalPorcentajeCuota'=>new Expression('SUM(uni_do_cm2)'),
                    'totalPorcentajeParticipacion'=>new Expression('SUM(uni_do_pct)')
                ))
                ->where(array('edi_in_cod'=>$edificioId,'uni_in_est'=>1));
            $queryTotalCuota=$sql->buildSqlString($selectUnidad);
            $rowTotalCuota=$adapter->query($queryTotalCuota,$adapter::QUERY_MODE_EXECUTE)->current();

            $responce['rows'][0]['id'] = 'TOTALES';
            $responce['rows'][0]['cell'] = array('','','','','<strong>'.$rowTotalCuota['totalAream2'].'</strong>','<strong>'.$rowTotalCuota['totalPorcentajeCuota'].'</strong>','<strong>'.$rowTotalCuota['totalPorcentajeParticipacion'].'</strong>',0,NULL,true,true,false);
            
            $i=1;
            foreach($unidadesPadre as $item)
            {   
                $subSelect=$sql->select()
                ->from($this->table)
                ->columns(array('total'=>new Expression('count(*)')))
                ->where(array('uni_in_pad'=>$item['uni_in_cod']));

                $subSelectString=$sql->buildSqlString($subSelect);
                $resulSubSelect=$adapter->query($subSelectString,$adapter::QUERY_MODE_EXECUTE)->current();

                $totalSubUnidades=$resulSubSelect['total'];

                $responce['rows'][$i]['id'] = $item['uni_in_cod'];

                $checkRow ="<center><label class='position-relative tooltipmsg'><input type='checkbox' id='chkCol". $item['uni_in_cod'] . "' name='chkCol". $item['uni_in_cod'] . "' class='ace' onclick='GridUnidad.checkBoxSelected(" . $item['uni_in_cod'] .");'/><span class='lbl'></span></label></center>";

                
                $leaft=$totalSubUnidades >=1 ? 'false':'true';

                $responce['rows'][$i]['cell'] = array( 
                    $checkRow,
                    $item['uni_vc_tip'].' '.$item['uni_vc_nom'], 
                    $item['residente'],
                    $item['usu_vc_ema'],
                    $item['uni_do_aocu'],
                    $item['uni_do_cm2'],
                    $item['uni_do_pct'],
                    '0',
                    "NULL",
                    $leaft,
                    $leaft,
                    false
                );
            
                $i++;
            }
            return $responce;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function buscarUnidad($paramSearch, $paramFilter)
    {

        $filter = "";   
        $search_ind = $paramSearch;
        if($search_ind == 'true'){
            $filter_cad = json_decode($paramFilter);
            $filter_opc = $filter_cad->{'groupOp'};
            $filter_rul = $filter_cad->{'rules'};
            $cont = 0;
            foreach($filter_rul as $key => $value){
                $fie = $filter_rul[$key]->{'field'};
                $opt = $this->search($filter_rul[$key]->{'op'});
                $dat = trim($filter_rul[$key]->{'data'});        

                //if($fie == 'unidad')  $fie = "CONCAT(uni.uni_vc_tip,' ',uni.uni_vc_nom)";
                if($fie == 'unidad')  $fie = "concat_ws(' ',uni_vc_tip,uni_vc_nom)";
                else if($fie == 'residente') $fie = "CONCAT(user.usu_vc_nom,' ',user.usu_vc_ape)";
                else $fie = "user.usu_vc_ema";

                if($cont==0){
                     if($opt=="REGEXP2"){
                        $opt = "LIKE";
                        $filter .= " AND ($fie $opt '%$dat%'";
                    }else{
                        if($opt=="REGEXP3"){
                            $opt = "LIKE";
                            $filter .= " AND ($fie $opt '%$dat%'";
                        }else{
                            if($opt=="NOT REGEXP2"){
                                $opt = "NOT LIKE";
                                $filter .= " AND ($fie $opt '%$dat%'";
                            }else{
                                if($opt=="NOT REGEXP3"){
                                    $opt = "NOT LIKE";
                                    $filter .= " AND ($fie $opt '%$dat%'";
                                }else{
                                    if($opt=='LIKE' || $opt=='NOT LIKE'){
                                        $filter .= " AND ($fie $opt '%$dat%'";
                                    }else{
                                        $filter .= " AND ($fie $opt '%$dat%'";
                                    }
                                }
                            }
                        }
                    }
                    $cont++;
                }else{
                  if($opt=="REGEXP2"){
                        $opt = "LIKE";
                        $filter .= " $filter_opc $fie $opt '%$dat%'";
                    }else{
                        if($opt=="REGEXP3"){
                            $opt = "LIKE";
                            $filter .= " $filter_opc $fie $opt '%$dat%'";
                        }else{
                            if($opt=="NOT REGEXP2"){
                                $opt = "NOT LIKE";
                                $filter .= " $filter_opc $fie $opt '%$dat%'";
                            }else{
                                if($opt=="NOT REGEXP3"){
                                    $opt = "NOT LIKE";
                                    $filter .= " $filter_opc $fie $opt '%$dat%'";
                                }else{
                                    if($opt=='LIKE' || $opt=='NOT LIKE'){
                                        $filter .= " $filter_opc $fie $opt '%$dat%'";
                                    }else{
                                        $filter .= " $filter_opc $fie $opt '%$dat%'";
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $filter .= ")";
        }

        return $filter;

    }

    private function search($oper){
        switch($oper){
            case "eq" : $oper = "="; break;
            case "ne" : $oper = "!="; break;
            case "lt" : $oper = "<"; break;
            case "le" : $oper = "<="; break;
            case "gt" : $oper = ">"; break;
            case "ge" : $oper = ">="; break;
            case "bw" : $oper = "REGEXP2"; break;
            case "bn" : $oper = "NOT REGEXP2"; break;
            case "in" : $oper = "IN"; break;
            case "ni" : $oper = "NOT IN"; break;
            case "ew" : $oper = "REGEXP3"; break;
            case "en" : $oper = "NOT REGEXP3"; break;
            case "cn" : $oper = "LIKE"; break;
            case "nc" : $oper = "NOT LIKE"; break;
        }
        return $oper;
    }

    public function getRowUnidad($unidadId){
        $sql=new Sql($this->adapter);
        $select=$sql->select()
            ->from(array('unidad'=>$this->table))
            ->columns(array('propietarioId'=>'uni_in_pro','residenteId'=>'uni_in_pos'))
            ->where(array('uni_in_cod'=>$unidadId));
            $statement=$sql->prepareStatementForSqlObject($select);
            $row=$statement->execute()->current();
            return $row;
    }

    public function getUnidadPorId($idUnidad,$idedificio){
        $sql=new Sql($this->adapter);

        $select=$sql->select()
            ->from('edificio')
            ->columns(array(
                'numeroSotano'=>'edi_in_nsot',
                'numeroPiso'=>'edi_in_npis'
            ))
            ->where(array('edi_in_cod'=>$idedificio));
        $rowEdificio=$sql->prepareStatementForSqlObject($select)->execute()->current();

        $select=$sql->select()
            ->from(array('unidad'=>$this->table))
            ->columns(array('uni_cod'=>'uni_in_cod','uni_pad'=>'uni_in_pad','propietarioId'=>'uni_in_pro',
                                'residenteId'=>'uni_in_pos','edificioId'=>'edi_in_cod','uni_nom'=>'uni_vc_nom',
                                'uni_dir'=>'uni_vc_dir','uni_pis'=>'uni_in_pis','uni_aocu'=>'uni_do_aocu',
                                'uni_bloque'=>'uni_vc_bloque','uni_tip'=>'uni_vc_tip','uni_nmun'=>'uni_vc_nmun',
                                'uni_uso'=>'uni_vc_uso','uni_aream2'=>'uni_do_aream2','uni_npar'=>'uni_vc_npar',
                                'uni_desc'=>'uni_te_desc','uni_cm2'=>'uni_do_cm2','uni_pct'=>'uni_do_pct'))
            ->where(array('uni_in_cod'=>$idUnidad));
        try {
            $statement=$sql->prepareStatementForSqlObject($select);
            $row=$statement->execute()->current();
            $row['padre'] = $this->esUnidadPadre($idUnidad);
            $row['numeroSotano'] = $rowEdificio['numeroSotano'];
            $row['numeroPiso'] = $rowEdificio['numeroPiso'];
            return $row;
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public function getAllUsuariosPorEdificio($edificioId){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select=$sql->select()
            ->from(array("usu"=>'usuario'))
            ->columns(array('id'=>'usu_in_cod','tipo'=>'usu_ch_tip','nombre'=>'usu_vc_nom','apellido'=>'usu_vc_ape'))
            ->where(array('usu_in_ent'=>$edificioId,'usu_in_est'=>1));
        $selectString=$sql->buildSqlString($select);
        $listaUsuarios=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $listaUsuarios;
    }

    public function getAllTiposDeUnidad(){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select=$sql->select()
            ->from(array("tu"=>'tipo_unidad'))
            ->columns(array('id'=>'tuni_in_cod','descripcion'=>'tuni_vc_desc'))
            ->where(array('tuni_in_est'=>1));
        $selectString=$sql->buildSqlString($select);
        $listaTipos=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $listaTipos;
    }

    public function getAllUnidadespadresPorEdificio($edificioId){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select=$sql->select()
            ->from(array("t"=>$this->table))
            ->columns(array('id'=>'uni_in_cod','descripcion'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")))
            ->where(array('edi_in_cod'=>$edificioId,'uni_in_est'=>1,'uni_in_pad'=>NULL));
        $selectString=$sql->buildSqlString($select);
        $listaUnidadesPadres=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $listaUnidadesPadres;
    }

    public function addUnidad($params){
		$adapter = $this->getAdapter();
		$sql = new Sql($adapter);
		$qe = "SELECT * FROM edificio WHERE edi_in_cod = {$params['edificioId']}";
		$dataEdificio = $adapter->query($qe, $adapter::QUERY_MODE_EXECUTE)->current();
		
		$unidadNombrer = str_replace("-", "", str_replace(".", "", str_replace(",", "", str_replace("/", "", $params['textNombren']))));
		$unidadDireccion = $dataEdificio['edi_vc_dir']." ".$dataEdificio['edi_vc_num']; // del query de edificio
		$unidadUrb = $dataEdificio['edi_vc_urb']; // del query de edificio

		$insert = $sql->insert('unidad');
		$data = array(
			'edi_in_cod'=> $params['edificioId'],
			'uni_in_pro'=>$params['selPropietario'],
            'uni_in_pos'=>$params['selResidente'],
			'uni_vc_nom'=> $params['textNombren'],
			'uni_vc_codpag'=> $unidadNombrer,
			'uni_vc_dir' => $unidadDireccion,
			'uni_vc_num' => $params['textNombren'],
			'uni_vc_urb' => $unidadUrb,
			'uni_do_aocu' => $params['textAreaOcupadan'],
			// 'uni_vc_bloque' => $data[$i]['C'],
			'uni_vc_tip' => $params['selTipon'],
			'uni_vc_nmun' => $params['textNroMunicipaln'],
			'uni_vc_uso' => $params['selUson'],
			'uni_do_aream2' => $params['textAream2n'],
			'uni_vc_npar' => $params['textNroPartidan'],
			'uni_te_desc' => $params["taDescripcionn"],
			'uni_do_cm2' => $params['textCuotan'],
			'uni_do_pct' => $params['textPctn'],
			'uni_do_deu' => 0,
			'uni_in_est' => 1,
			'uni_in_ultingpar' => 0
		);

		$insert->values($data);
		$idRegistro = $sql->prepareStatementForSqlObject($insert)->execute()->getGeneratedValue();

		$respuesta = array(
			"message" => "success",
			"tipo" => 0,
			"mensaje" => "Registro exitoso."
		);

		return $respuesta;
    }

    public function updateUnidad($params){
        $fechaSistema=date('Y-m-d h:i:s');
        $respuesta=array("tipo"=>0,"mensaje"=>"Ocurrió un error desconocido en el servidor");
        $data=array(
            'uni_in_pro'=>$params['selPropietario'],
            'uni_in_pos'=>$params['selResidente'],
            'uni_vc_nom'=>$params['textNombre'],
            'uni_vc_dir'=>$params['textDireccion'],
            'uni_in_pis'=>$params['selPiso'],
            'uni_do_aocu'=>$params['textAreaOcupada'],
            'uni_vc_tip'=>$params['selTipo'],
            'uni_vc_nmun'=>$params['textNroMunicipal'],
            'uni_vc_uso'=>$params['selUso'],
            'uni_do_aream2'=>$params['textAream2'],
            'uni_vc_npar'=>$params['textNroPartida'],
            'uni_te_desc'=>$params['taDescripcion'],
            'uni_do_cm2'=>$params['textCuota'],
            'uni_do_pct'=>$params['textPct'],
            'uni_ti_fedit'=>$fechaSistema
        );

        $unidadId=(int)$params['id'];
        $unidadPadreId=(int)$params['selUnidadPadre'];

        if($unidadPadreId>0){
            if($this->esUnidadPadre($unidadId)){
                $respuesta=array(
                    "tipo"=>0,
                    "mensaje"=>"Esta unidad es principal, no es posible asociarla."
                );
                return $respuesta;
            }

            $rowUnidadPadre=$this->getRowUnidad($unidadPadreId);
            $data['uni_in_pro']=$rowUnidadPadre['propietarioId'];
            $data['uni_in_pos']=$rowUnidadPadre['residenteId'];

            $data['uni_in_pad']=$unidadPadreId;
            $data['uni_ti_fasig']=$fechaSistema;
        }else{
            $data['uni_in_pad']=NULL;
        }

        
        $sql=new Sql($this->adapter);
        $update=$sql->update()
            ->table($this->table)
            ->set($data)
            ->where(array('uni_in_cod'=>$unidadId));
        try{
            $statement=$sql->prepareStatementForSqlObject($update);
            $resultado=$statement->execute()->count();
            /////////save auditoria////////
            $params['tabla']='Unidad';
            $params['numRegistro']=$unidadId;
            $params['accion']='Editar';
            $this->saveAuditoria($params);
            ///////////////////////////////
            $respuesta=array(
                "message"=>"success",
                "tipo"=>0,
                "mensaje"=>"Esta unidad es principal, no es posible asociarla."
            );
        } catch (\Exception $err){
           throw $err;
        }
        return $respuesta;
    }

    public function deleteUnidad($id){
        $sql=new Sql($this->adapter);
        $delete=$sql->delete()
        ->table($this->table)
        ->where(array('uni_in_cod'=>$id));
    }

    //retorna true o false
    private function esUnidadPadre($unidadId){
        $resultado=false;
        $sql=new Sql($this->adapter);
        $select=$sql->select();
            $select->from($this->table)->columns(array('total'=>new Expression('count(*)')))
            ->where(array('uni_in_pad'=>$unidadId))
            ->where($select->where->notEqualTo('uni_in_est',0));
        
        $statement=$sql->prepareStatementForSqlObject($select);

        try {
            $rsCount=$statement->execute()->current();

            if($rsCount['total']>0){
                $resultado=true;
            }
        } catch (Exception $err) {
            throw $err;
        }
        return $resultado;
    }




    public function crearExcelUnidades($params)
    {   

        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $name = 'RPT_UNIDADES-'.time().'.xlsx';
        $file = 'public/temp/reportes/'.$name;

        $objPHPExcel = new \PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('UNIDADES');
        
        $edificioId=$params['edificioId'];
    
        //set titulos

        $objSheet->setCellValue('A1', 'UNIDADES - '.$this->getNombreEdificio($edificioId));

        // set header
        $objSheet->setCellValue('A2', 'UNIDAD');
        $objSheet->setCellValue('B2', 'PROPIETARIO');
        $objSheet->setCellValue('C2', 'CORREO');
        $objSheet->setCellValue('D2', 'RESIDENTE');
        $objSheet->setCellValue('E2', 'CORREO');
        $objSheet->setCellValue('F2', 'ÁREA M2');
        $objSheet->setCellValue('G2', '% PARTICIPACION');
        $objSheet->setCellValue('H2', '% CUOTA');
        $objSheet->setCellValue('I2', 'NUMERO');
        $objSheet->setCellValue('J2', 'PISO');
        $objSheet->setCellValue('K2', 'ASOCIADO A');
        

        
        $selectString="SELECT u.uni_in_cod as id ,
                            CONCAT(u.uni_vc_tip,' ' ,u.uni_vc_nom ) AS unidadnombre,
                            u.uni_do_aocu as area_m2,
                            u.uni_do_pct as pct_participacion ,
                            u.uni_do_cm2 as pct_cuota ,
                            u.uni_vc_num as numero_municipal,
                            u.uni_in_pis as piso,
                            CONCAT(unidadpadre.uni_vc_tip,' ',unidadpadre.uni_vc_nom ) AS unidadpadre, 
                            CONCAT(propietario.usu_vc_nom , propietario.usu_vc_ape) as propietario,
                            propietario.usu_vc_ema as correopropietario,
                            CONCAT(residente.usu_vc_nom , residente.usu_vc_ape) as residente,
                            residente.usu_vc_ema as correoresidente
                            from unidad as u
                            left join usuario propietario on propietario.usu_in_cod=u.uni_in_pro
                            left join usuario residente on residente.usu_in_cod=u.uni_in_pos
                            left join unidad unidadpadre on unidadpadre.uni_in_cod=u.uni_in_pad
                        WHERE u.edi_in_cod =".$edificioId." AND u.uni_in_est=1 ORDER BY id asc" ;
       


        $rowsUnidades=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        

        $indexFila=3;
        foreach($rowsUnidades as $key=>$value){
           
            $objSheet->setCellValue('A'.$indexFila, $value['unidadnombre']);
            $objSheet->setCellValue('B'.$indexFila, $value['propietario']);
            $objSheet->setCellValue('C'.$indexFila, $value['correopropietario']);
            $objSheet->setCellValue('D'.$indexFila, $value['residente']);
            $objSheet->setCellValue('E'.$indexFila, $value['correoresidente']);
            $objSheet->setCellValue('F'.$indexFila, $value['area_m2']);
            $objSheet->setCellValue('G'.$indexFila, $value['pct_participacion']);
            $objSheet->setCellValue('H'.$indexFila, $value['pct_cuota']);
            $objSheet->setCellValue('I'.$indexFila, $value['numero_municipal']);
            $objSheet->setCellValue('J'.$indexFila, $value['piso']);
            $objSheet->setCellValue('K'.$indexFila, $value['unidadpadre']);        

            $indexFila++;
        }


        $objSheet->mergeCells('A1:K1');
        $objSheet->getStyle('A1')->getFont()->setBold(true);
        $objSheet->getStyle('A1')->applyFromArray(array('alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));


        $objSheet->getStyle('A2:K2')->getAlignment()->setWrapText(true);
        $styleHeader = array(
            'rgb' => 'FFFFFF',
            'size'=>10,
            'alignment' => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'C15F9D')
            )
        );
        $objSheet->getStyle('A2:k2')->applyFromArray($styleHeader);

        $listAbecedario=array("A","B","C","D","E","F","G","H","I",'J','K','L');
        $listWidthColum=array("A"=>'20',"B"=>"50","C"=>"50","D"=>"50","E"=>"50",
                            "F"=>"10","G"=>"10","H"=>"10","I"=>"5","J"=>"20","K"=>"20");
        for($i=0;$i<8;$i++){
            $objSheet->getColumnDimension($listAbecedario[$i])->setAutoSize( false );
            $objSheet->getColumnDimension($listAbecedario[$i])->setWidth((int)($listWidthColum[$listAbecedario[$i]]));

        }

        //BORDER CELL
        $objSheet->getStyle('A1:K1'.(count($rowsUnidades)+2))
            ->getBorders()->applyFromArray(
                    array(
                        'allborders' => array(
                            'style' => \PHPExcel_Style_Border::BORDER_DASHED,
                            'color' => array(
                                'rgb' => '000000'
                                )
                            )
                    )
                );

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($file);

        if(file_exists($file)){
            /////////save auditoria////////
            $params['tabla']='';
            $params['numRegistro']='';
            $params['accion']='Exportar Excel';
            $this->saveAuditoria($params);
            ///////////////////////////////
            $response = array(
                "message"=>"success",
                "ruta"=>"temp/reportes/".$name,
                "nombreFile"=>$name
                );
        }else{
            $response = array("message"=>"nofile");
        }

        return $response;
    }


    private function getNombreEdificio($edificioId){
        $sql=new Sql($this->adapter);
        $selectEdificio=$sql->select()
            ->from(array('edi'=>'edificio'))
            ->columns(array('nombre'=>'edi_vc_des'))
            ->where(array('edi_in_cod'=>$edificioId));

        $statement=$sql->prepareStatementForSqlObject($selectEdificio);
        $rsEdificio=$statement->execute()->current();

        return $rsEdificio['nombre']; 
    }

    private function saveAuditoria($params)
    {   
        $adapter=$adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['idUsuario'], //idusuario
            'edi_in_cod'=> $params['edificioId'], //idedificio
            'aud_opcion'=> 'Mantenimiento > Unidades', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> $params['tabla'], //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> '' //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
	}
	
	public function formato($idedificio,$idusuario,$accion,$params) {
		$adapter = $this->getAdapter();
		$sql=new Sql($adapter);
		$name = 'formato-'.time().'.xlsx';
		$file = 'public/temp/departamento/'.$name;

		switch ($accion) {
			case 'dowload':
				$objPHPExcel = new \PHPExcel();
		        $objSheet = $objPHPExcel->getActiveSheet();
		        $objSheet->setTitle('Formato de ejemplo');
		        $objSheet->getStyle('A1:H1')->getFont()->setBold(true);
				
				$objSheet->setCellValue('A1', 'UNIDAD');
		        $objSheet->setCellValue('B1', 'AREA OCUPADA');
		        $objSheet->setCellValue('C1', 'BLOQUE');
		        $objSheet->setCellValue('D1', 'TIPO');
		        $objSheet->setCellValue('E1', 'USO');
				$objSheet->setCellValue('F1', 'DESCRIPCION');
				$objSheet->setCellValue('G1', 'CUOTA M2');
				$objSheet->setCellValue('H1', 'PORCENTAJE');
		        
		        // $selectUnidadData=$sql->select()
				// 	->from('unidad')
				// 	->columns(array(
				// 		'unidad'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")
				// 	))
				// 	->where(array(
				// 		'edi_in_cod'=>$idedificio,
				// 		'uni_in_est'=>1,
				// 		'uni_in_pad IS NULL'
				// 	));
				// $selectUnidad=$sql->buildSqlString($selectUnidadData);
				// $dataUnidad=$adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
				// $totalUnidades = count($dataUnidad) + 1;
				
				// $i=2;
				// foreach($dataUnidad as $val){
				// 	$objSheet->setCellValue('A'.$i++, $val['unidad']);
				// }

				$row = array("A", "B", "C", "D", "E", "F", "G", "H");

		        foreach($row as $val){            
		            $objSheet->getColumnDimension($val)->setAutoSize(true);
		        }
		        
		        $objSheet->getStyle('A1:H1')->getFont()->getColor()->applyFromArray( array('rgb' => 'FFFFFF') );
		        $objSheet->getStyle('A1:H1')->applyFromArray(
			        array(
			            'fill' => array(
			                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
			                'color' => array('rgb' => 'C15F9D')
			            )
			        )
				);
				
				$objPHPExcel->getActiveSheet()->getStyle('A1:H1')->getBorders()->applyFromArray(
					array(
						'allborders' => array(
							'style' => \PHPExcel_Style_Border::BORDER_DASHDOT,
							'color' => array(
								'rgb' => '000000'
							)
						)
					)
				);
		        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		        $objWriter->save($file);
		        
		        if(file_exists($file)){
					/////////save auditoria////////
					$params['tabla']='';
					$params['numRegistro']='';
					$params['accion']='Descarga Formato';
					$this->saveAuditoria($params);
					///////////////////////////////
		        	$response = array(
		        		"message"=>"success",
		        		"cuerpo"=>"formato generado correctamente...",
		        		"ruta"=>"temp/departamento/".$name,
		        		"nombreFile"=>$name
		        	);
		        }else{
		        	$response = array("message"=>"nofile");
		        }

			break;
			case 'delete':
				$archivo = 'public/temp/departamento/'.$params['file'];
				if(file_exists($archivo)) {
					@unlink($archivo);
					$response = array(null);
				}
				break;
			default:
				return null;
				break;
		}
        return $response;
	}

}