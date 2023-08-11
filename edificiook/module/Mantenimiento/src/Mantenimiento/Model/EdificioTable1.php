<?php



/**

* edificioOk (https://www.edificiook.com)

* creado por: Jhon Gómez, 11/03/2016.

* ultima modificacion por: Jhon Gómez

* Fecha Modificacion: 11/04/2016.

* Descripcion: Modelo Edificio

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



class EdificioTable extends AbstractTableGateway{



    public $table="edificio";



    public function __construct(Adapter $adapter)

    {

        $this->adapter=$adapter;

    }



    public function listaCargoJuntaDirectiva()

    {

        return [];

        $adapter=$this->getAdapter();

        $sql=new Sql($adapter);

        $select = $sql->select();

        $select->from('cargo');

        $select->columns(array("car_ch_cod","car_vc_des"));

        $select->where(array("car_in_est"=>1));

        $selectString=$sql->buildSqlString($select);

        $data = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        return $data;

    }



    public function listarUsuarios($codempresa,$codedificio)

    {

        $adapter=$this->getAdapter();

        $sql=new Sql($adapter);

        $select = $sql->select();

        $select->from('usuario');

        $select->columns(array("usu_in_cod","usu_ch_tip","usu_vc_ape","usu_vc_nom"));



        if ($codempresa == 2) {

            $select->quantifier('DISTINCT');

        }else{

            if($codedificio==38){

                $select->quantifier('DISTINCT');

                $select->where(array("emp_in_cod"=>$codempresa, "usu_in_ent"=>37));

            }else{

                $select->quantifier('DISTINCT');

                $select->where(array("usu_in_ent"=>$codedificio));

            }

        }



        $selectString=$sql->buildSqlString($select);

        $data = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        return $data;

    }



    public function updateEdificio($params,$idedificio,$file)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $accion = $params['accion'];

        switch ($accion)
        {
            case 'datos-generales':


                /*
                $dataAmbienteComunes = $params['textAmbienteComunes'];
                $delete=$sql->delete()
                    ->from('lugar_edificio')
                    ->where(array('edi_in_cod'=>$idedificio));
                $sql->prepareStatementForSqlObject($delete)->execute();
                $resultadoInsertLugarEdificio = 0;

                if(count($dataAmbienteComunes) > 0)
                {
                    foreach($dataAmbienteComunes as $ambientesComunes){
                        $insert = $sql->insert('lugar_edificio');
                        $dataLugar = array(
                            'edi_in_cod'=> $idedificio,
                            'lug_in_cod'=> $ambientesComunes
                        );
                        $insert->values($dataLugar);
                        $resultadoInsertLugarEdificio = $sql->prepareStatementForSqlObject($insert)->execute()->count();
                    }
                }*/

                $data = array(
                    'tip_in_cod'=>$params['selIdTipo'],
                    'dis_in_cod'=>$params['iddistrito'],
                    'edi_vc_des'=>$params['textNombre'],
                    'edi_vc_dir'=>$params['textDireccion'],
                    'edi_da_fcon'=>$params['textFechaConst'],
                    'edi_vc_num'=>$params['textNumero'],
                    'edi_do_auti'=>$params['textAreaUtil'],
                    'edi_vc_urb'=>$params['textUrbanizacion'],
                    'edi_in_nuni'=>$params['textNumeroUnidades'],
                    'edi_in_npis'=>$params['textNumeroPisos'],
                    'edi_in_nsot'=>$params['textNumeroSotanos'],
                    'edi_do_atot'=>$params['textAreaTotal'],
                    'edi_do_aocu'=>$params['textAreaOcupada'],
                    'edi_do_aexc'=>$params['textAreaPropiedadExclusiva'],
                    'edi_do_ater'=>$params['textAreaTerreno'],
                    'edi_vc_npr'=>$params['textNumeroPartRegist']
                );

                //actualizar registros por id
                $update=$sql->update()
                ->table($this->table)
                ->set($data)
                ->where(array('edi_in_cod'=>$idedificio));

                try {
                    $statement=$sql->prepareStatementForSqlObject($update);
                    $resultado=$statement->execute()->count();

                    if($file['logoedificio']['name']!='')
                    {

                        $nombre = $file['logoedificio']['name'];
                        $size = $file['logoedificio']['size'];
                        $temp = $file['logoedificio']['tmp_name'];
                        $type = $file['logoedificio']['type'];
                        $imagenExtension = substr($nombre, strrpos($nombre, '.')+1);

                        //update imagen
                        $data = array("edi_vc_logo"=>$idedificio.".".$imagenExtension);
                        $update=$sql->update()
                            ->table('edificio')
                            ->set($data)
                            ->where(array('edi_in_cod'=>$idedificio));
                        $statement=$sql->prepareStatementForSqlObject($update)->execute();
                        //save imagen
                        $carpetaLogo = 'public/file/logo-edificio/'.$idedificio.'.'.$imagenExtension;
                        copy($temp, $carpetaLogo);
                    }

                    if($resultado == 1 || $resultadoInsertLugarEdificio == 1){
                        /////////save auditoria////////
                        $params['numRegistro']=$idedificio;
                        $params['accion']='Editar';
                        $this->saveAuditoria($params);
                        $params['ruta']='Edificio > Datos Generales';
                        ///////////////////////////////
                        $mensaje = array("tipo"=>1,"cuerpo"=>"Resgistro actualizado correctamente...");
                    }else{
                        $mensaje = array("tipo"=>0,"cuerpo"=>"No se a actualizado ningun campo...");
                    }

                }catch (\Exception $err) {
                    throw $err;
                }


            break;



            case 'cargos-y-otros':

                //$juntaDirectiva = $this->updateJuntaDirectiva($idedificio,$params['juntaDirectiva'],$params['fechaActual']);

                //valores
                $data = array(
                    'edi_vc_tcuo'=>$params['selTipoDeCuota'],
                    'edi_ch_sup'=>$params['selSupervidor'],
                    'edi_ch_con'=>$params['selConserje'],
                    'edi_in_adm'=>$params['selAdministrador'],
                    'edi_vc_tiprec'=>$params['selRecaudacion'],
                    'edi_vc_tpre'=>$params['selTipoPresupuesto'],
                    'edi_in_cobleg'=>(isset($params['chkCobranzaLegal']))?1:2,
                    'edi_in_mor'=>$params['rbMorosos']
                );

                //actualizar
                $update=$sql->update()
                ->table($this->table)
                ->set($data)
                ->where(array('edi_in_cod'=>$idedificio));

                try {
                    $resultado=$sql->prepareStatementForSqlObject($update)->execute()->count();
                    //guardar pdf (reglamento interno)
                    if(isset($file['reglamentoInterno']['name']) && $file['reglamentoInterno']['name']!=''){
                        $nombre = $file['reglamentoInterno']['name'];
                        $size = $file['reglamentoInterno']['size'];
                        $temp = $file['reglamentoInterno']['tmp_name'];
                        $type = $file['reglamentoInterno']['type'];

                        $carpetaReglamentoInterno = 'public/file/reglamento-interno/'.$idedificio.'-rinterno.pdf';
                        copy($temp, $carpetaReglamentoInterno);
                        if(file_exists($carpetaReglamentoInterno)){
                            $this->saveArchivo('edi_vc_regint',$idedificio,'rinterno.pdf');
                        }

                    }else{
                        if($params['delReglamentoInterno']!='undefined'){
                            $this->deleteArchivo('edi_vc_regint',$idedificio,$idedificio.'-rinterno.pdf');
                        }
                    }

                    //guardar pdf (manual operaciones)
                    if(isset($file['manualOperaciones']['name']) && $file['manualOperaciones']['name']!=''){
                        $nombre = $file['manualOperaciones']['name'];
                        $size = $file['manualOperaciones']['size'];
                        $temp = $file['manualOperaciones']['tmp_name'];
                        $type = $file['manualOperaciones']['type'];

                        $carpetaManualOperaciones = 'public/file/manual-operaciones/'.$idedificio.'-moperaciones.pdf';
                        copy($temp, $carpetaManualOperaciones);
                        if(file_exists($carpetaManualOperaciones)){
                            $this->saveArchivo('edi_vc_manope',$idedificio,'moperaciones.pdf');
                        }

                    }else{
                        if($params['delManualOperaciones']!='undefined'){
                            $this->deleteArchivo('edi_vc_manope',$idedificio,$idedificio.'-moperaciones.pdf');
                        }
                    }
                    //if($resultado == 1 || $juntaDirectiva == 'success'){
                    if($resultado == 1){
                        /////////save auditoria////////
                        $params['numRegistro']=$idedificio;
                        $params['accion']='Editar';
                        $this->saveAuditoria($params);
                        $params['ruta']='Edificio > Cargos y Otros';
                        ///////////////////////////////
                        $mensaje = array("tipo"=>1,"cuerpo"=>"Resgistro actualizado correctamente...");  
                    }else{
                        $mensaje = array("tipo"=>0,"cuerpo"=>"No se a actualizado ningun campo...");
                    }
                } catch (\Exception $err) {
                    throw $err;
                }

            break;



            case 'configuracion':



                $tipoCalculo = (isset($params['selTipoCalculo']))?$params['selTipoCalculo']:null;

                $mora = (isset($params['chkAplicarMora']))?'SI':'NO';

                $cobroConsumo = (isset($params['chkCobroDeConsumo']))?1:0;



                $data = array(

                    'edi_vc_nompro'=>$params['textRazonSocial'],

                    'edi_ch_rucpro'=>$params['textRuc'],

                    'edi_te_men'=>$params['textMensaje'],

                    'edi_in_diapro'=>$params['selDiaDeProceso'],

                    'edi_in_diapag'=>$params['selDiaDePago'],

                    'edi_vc_tipdoc'=>$params['selTipoDeDocumento'],

                    'edi_in_cobcon'=>$cobroConsumo,

                    'edi_in_tipcon'=>$tipoCalculo,

                    'edi_in_round'=>$params['selFormatoCuota'], //ok

                    'edi_ch_aplimora'=>$mora,

                    'edi_do_moraxdia'=>$params['textCostoMoraPorDia'],

                    'edi_in_numser'=>$params['textNumeroSerie'],

                    'edi_in_numdoc'=>$params['textNumeroDocumento'],

                    'edi_vc_nomcuerec'=>$params['textNombreCuentaRecaudadora'],

                    'edi_do_monmin'=>$params['textMontoMinimo'],

                    'edi_vc_numcue'=>$params['textNumeroDeCuenta']

                );



                //actualizar registros por id

                $update=$sql->update()

                ->table($this->table)

                ->set($data)

                ->where(array('edi_in_cod'=>$idedificio));



                try {

                    $statement=$sql->prepareStatementForSqlObject($update);

                    $resultado=$statement->execute()->count();

                    if($resultado == 1){
                        /////////save auditoria////////
                        $params['numRegistro']=$idedificio;
                        $params['accion']='Editar';
                        $this->saveAuditoria($params);
                        $params['ruta']='Edificio > Configuración';
                        ///////////////////////////////
                        $mensaje = array("tipo"=>1,"cuerpo"=>"Resgistro actualizado correctamente...");
                    }else{
                        $mensaje = array("tipo"=>0,"cuerpo"=>"No se a actualizado ningun campo...");
                    }

                } catch (\Exception $err) {

                    throw $err;

                }



            break;



        }



        return $mensaje;



    }

    private function deleteArchivo($atributo,$idedificio,$archivo)
    {  
        //nombre del archivo
        $directorio = explode("-", $archivo);
        $directorio = ($directorio[1]=='rinterno.pdf')?'reglamento-interno':'manual-operaciones';
        //actualizar archivo    
        $adapter = $this->getAdapter();
        $sql=new Sql($adapter);
        $data = array($atributo=>'');
        $update=$sql->update()
            ->table($this->table)
            ->set($data)
            ->where(array('edi_in_cod'=>$idedificio));
        $result = $sql->prepareStatementForSqlObject($update)->execute()->count();
        //eliminar archivo
        if($result == 1){
            @unlink('public/file/'.$directorio.'/'.$archivo);
        }


    }

    private function saveArchivo($atributo,$idedificio,$nombre)
    {
        $adapter = $this->getAdapter();
        $sql=new Sql($adapter);
        $data = array($atributo=>$idedificio.'-'.$nombre);
        $update=$sql->update()
            ->table('edificio')
            ->set($data)
            ->where(array('edi_in_cod'=>$idedificio));
        $sql->prepareStatementForSqlObject($update)->execute();
    }

    private function mostrarSerieDocumento($numeroSerie,$numeroDocumento)

    {

        $adapter = $this->getAdapter();

        $resPlan = "SELECT ing_in_nroser, ing_in_nrodoc FROM ingreso i, unidad ui WHERE i.uni_in_cod = ui.uni_in_cod AND ui.edi_in_cod = 46 ORDER BY ing_in_nroser DESC, ing_in_nrodoc DESC LIMIT 0, 1 ";

        $rowNumero = $adapter->query($resPlan,$adapter::QUERY_MODE_EXECUTE)->current();

        $nroSerie = '';

        $nroDocumento = '';

        if ($rowNumero['ing_in_nroser'] != '0' && $rowNumero['ing_in_nrodoc'] != '0' && $rowNumero['ing_in_nroser'] != '' && $rowNumero['ing_in_nrodoc'] != '') {

            $nroSerie = $rowNumero['ing_in_nroser'];

            $nroDocumento = $rowNumero['ing_in_nrodoc'];

        } else {

            $nroSerie = $numeroSerie;

            $nroDocumento = $numeroDocumento;

        }

        return $nroSerie . ' - ' . $nroDocumento;

    } 



    private function updateJuntaDirectiva($idedificio, $juntaDirectiva, $fechaActual)

    {

        $adapter = $this->getAdapter();

        //$db = new MySQL();

        $resPlan = "SELECT pla_in_cod FROM edificio WHERE edi_in_cod = ".$idedificio;

        $rowPlan = $adapter->query($resPlan,$adapter::QUERY_MODE_EXECUTE)->current();

        $codPlan = $rowPlan['pla_in_cod'];



        if($codPlan!=5){

                return 'success';

                $ResValPre = "SELECT jd.usu_in_cod, c.car_ch_cod FROM junta_directiva jd, cargo c WHERE edi_in_cod='$idedificio' AND c.car_vc_des='Presidente'";

                $RowValPre = $adapter->query($ResValPre,$adapter::QUERY_MODE_EXECUTE)->current();



                $ResUpdPre = "UPDATE usuario_edificio SET uedi_da_ffin='$fechaActual', uedi_in_est='0' WHERE usu_in_cod='" . $RowValPre['usu_in_cod'] . "' AND edi_in_cod='$idedificio' AND car_ch_cod='" . $RowValPre['car_ch_cod'] . "'";

                $adapter->query($ResUpdPre,$adapter::QUERY_MODE_EXECUTE);



                $deleteJuntaDirectiva = "DELETE FROM junta_directiva WHERE edi_in_cod='$idedificio'";

                $adapter->query($deleteJuntaDirectiva,$adapter::QUERY_MODE_EXECUTE);



                if (count(trim($juntaDirectiva)) > 0) {

                    $juntad = explode("#", $juntaDirectiva);

                    for ($i = 1; $i < count($juntad); $i++) {

                        $jun = trim($juntad[$i]);

                        if ($jun != '') {

                            $nomarr = explode("   ->   ", $jun);

                            $nomb = trim($nomarr[0]);



                            $res_cod_usu = "SELECT usu_in_cod FROM usuario WHERE CONCAT(usu_vc_ape,' ',usu_vc_nom)='$nomb' ";

                            $row_cod_usu = $adapter->query($res_cod_usu,$adapter::QUERY_MODE_EXECUTE)->current();



                            $carg = trim($nomarr[1]);



                            $res_cod_car = "SELECT * FROM cargo WHERE car_vc_des='" . $carg . "' ";

                            $row_cod_car = $adapter->query($res_cod_car,$adapter::QUERY_MODE_EXECUTE)->current();



                            $insertJuntaDirectiva = "INSERT INTO junta_directiva VALUES ('" . $idedificio . "','" . $row_cod_usu['usu_in_cod'] . "','" . $row_cod_car['car_ch_cod'] . "')";

                            $adapter->query($insertJuntaDirectiva,$adapter::QUERY_MODE_EXECUTE);



                            if ($carg == 'PRESIDENTE') {

                                $ResInsPre = "INSERT INTO usuario_edificio VALUES ('$idedificio','" . $row_cod_usu['usu_in_cod'] . "','" . $row_cod_car['car_ch_cod'] . "','$fechaActual','','1' )";

                                $adapter->query($ResInsPre,$adapter::QUERY_MODE_EXECUTE);



                                $queryPermisoCargo = "SELECT p.per_ch_cod FROM permiso p, cargo c WHERE p.car_ch_cod=c.car_ch_cod AND c.car_vc_des='Presidente' ";

                                $ResPerPre = $adapter->query($queryPermisoCargo,$adapter::QUERY_MODE_EXECUTE)->toArray();



                                foreach ($ResPerPre as $RowPerPre) {

                                    $deleteAcciones = "DELETE FROM acciones WHERE usu_in_cod='" . $RowValPre['usu_in_cod'] . "' AND edi_in_cod='$idedificio' AND per_ch_cod='" . $RowPerPre['per_ch_cod'] . "' ";

                                    $adapter->query($deleteAcciones,$adapter::QUERY_MODE_EXECUTE);

                                    

                                    $insertAcciones = "INSERT INTO acciones VALUES ('" . $RowPerPre['per_ch_cod'] . "','$idedificio','" . $row_cod_usu['usu_in_cod'] . "','0','0','0','0','0','0','0','0','0','1' ) ";

                                    $adapter->query($insertAcciones,$adapter::QUERY_MODE_EXECUTE);

                                }

                            }

                        }

                    }

                }

        }



        return 'success';

    }



    public function listarJuntaDirectiva($idedificio)

    {

        return [];

        $adapter = $this->getAdapter();

        $query = "SELECT * FROM junta_directiva jd, cargo c WHERE c.car_ch_cod=jd.car_ch_cod AND jd.edi_in_cod='$idedificio'";

        $dataJuntaCargo = $adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->toArray();



        $dataJuntaDirectiva= array();

        $i=0;

        foreach($dataJuntaCargo as $juntaCargo){

            $idusuario = $this->consultarUsuarioId($juntaCargo['usu_in_cod']);

            $nombresUsuario = $this->consultarUsuarioNombres($juntaCargo['usu_in_cod']);

            $idcargo = $juntaCargo['car_ch_cod'];

            $nombreCargo = $juntaCargo['car_vc_des'];



            $dataJuntaDirectiva[$i] = array(

                "idusuario"=>$idusuario,

                "idcargo"=>$idcargo,

                "nombresUsuario"=>$nombresUsuario,

                "nombreCargo"=>$nombreCargo,

            );



            $i++;

        }



        return $dataJuntaDirectiva;



    }



    private function consultarUsuarioId($idusuario)

    {

        $adapter=$this->getAdapter();

        $sql=new Sql($adapter);

        $select = $sql->select();

        $select->from('usuario');

        $select->where(array('usu_in_cod'=>$idusuario));

        $queryEdificio=$sql->buildSqlString($select);

        $dataUser = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();

        return $dataUser['usu_in_cod'];

    }



    private function consultarUsuarioNombres($idusuario)

    {

        $adapter=$this->getAdapter();

        $sql=new Sql($adapter);

        $select = $sql->select();

        $select->from('usuario');

        $select->where(array('usu_in_cod'=>$idusuario));

        $queryEdificio=$sql->buildSqlString($select);

        $dataUser = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();

        return $dataUser['usu_vc_ape'] . " " . $dataUser['usu_vc_nom'];

    }



    public function listarLugar()

    {

        $adapter=$this->getAdapter();

        $sql=new Sql($adapter);

        $select = $sql->select();

        $select->from('lugar');

        $select->columns(array("id"=>"lug_in_cod","descripcion"=>"lug_vc_des"));

        $selectString=$sql->buildSqlString($select);

        //return $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        return [];
    }



    public function listarEdificioPorId($id)

    {
        //adaptador
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        //query
        $select = $sql->select();
        $select->from($this->table);
        $select->columns(array("id"=>"edi_in_cod","idtipo"=>"tip_in_cod","iddistrito"=>"dis_in_cod","nombre"=>"edi_vc_des","direccion"=>"edi_vc_dir","fechaConst"=>"edi_da_fcon","numero"=>"edi_vc_num","areaUtil"=>"edi_do_auti","urbanizacion"=>"edi_vc_urb","numeroUnidades"=>"edi_in_nuni","numeroPisos"=>"edi_in_npis","numeroSotanos"=>"edi_in_nsot","areaTotal"=>"edi_do_atot","areaOcupada"=>"edi_do_aocu","areaPropiedadExclusiva"=>"edi_do_aexc","areaTerreno"=>"edi_do_ater","numeroPartida"=>"edi_vc_npr","logo"=>"edi_vc_logo","banner"=>"edi_vc_img","edi_in_adm","edi_ch_sup","edi_ch_con","tipoCuota"=>"edi_vc_tcuo","recaudacion"=>"edi_vc_tiprec","presupuesto"=>"edi_vc_tpre","cobranzaLegal"=>"edi_in_cobleg","reglamentoInterno"=>"edi_vc_regint","manualOperaciones"=>"edi_vc_manope","razonSocial"=>"edi_vc_nompro","ruc"=>"edi_ch_rucpro","mensaje"=>"edi_te_men","diaDeProceso"=>"edi_in_diapro","diaDePago"=>"edi_in_diapag","tipoDeDocumento"=>"edi_vc_tipdoc","cobroDeConsumo"=>"edi_in_cobcon","tipoCalculo"=>"edi_in_tipcon","formatoCuota"=>"edi_in_round","aplicarMora"=>"edi_ch_aplimora","costoMoraPorDia"=>"edi_do_moraxdia","numeroSerie"=>"edi_in_numser","numeroDocumento"=>"edi_in_numdoc","nombreCuentaRecaudadora"=>"edi_vc_nomcuerec","montoMinimo"=>"edi_do_monmin","numeroDeCuenta"=>"edi_vc_numcue", "morosos"=>"edi_in_mor"));

        $select->where(array('edi_in_cod' => $id));
        $selectString=$sql->buildSqlString($select);
        $data = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        $data['banner'] = ($data['banner']!='')?'file/banner-edificio/'.$data['banner']:'images/no-image.png';
        $data['logo'] = ($data['logo']!='')?'file/logo-edificio/'.$data['logo']:'images/no-image.png';
        $filaDistrito = $this->getUbigeo($data['iddistrito']);
        $data['distrito'] = $filaDistrito['dis_vc_nom'];
        $data['provincia'] = $filaDistrito['prv_vc_nom'];
        $data['departamento'] = $filaDistrito['dep_vc_nom'];
        $data['serieDocumento'] = $this->mostrarSerieDocumento($data['numeroSerie'],$data['numeroDocumento']);
        return $data;

    }



    private function getUbigeo($iddistrito)

    {

        $adapter=$this->getAdapter();

        $sql = new Sql($adapter);

        $select = $sql->select()

        ->from(array('dis' => 'distrito'), array('dis_in_cod'))

        ->join(array('pro' => 'provincia'), 'dis.prv_in_cod = pro.prv_in_cod', array('prv_vc_nom'))

        ->join(array('dep' => 'departamento'), 'pro.dep_in_cod = dep.dep_in_cod', array('dep_vc_nom'))  

        ->where(array('dis.dis_in_cod'=>$iddistrito));

        $selectString = $sql->buildSqlString($select);

        $data = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE)->current();

        return $data;

    }



    public function getLugarDeEntidadPorEdificio($idedificio)

    {

        return null;

        $adapter=$this->getAdapter();

        $sql=new Sql($adapter);

        $select = $sql->select()

                ->from('lugar_edificio')

                ->columns(array('lug_in_cod'))

                ->where(array('edi_in_cod' => $idedificio));

        $selectString=$sql->buildSqlString($select);

        $rowset = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        if(count($rowset) > 0){

            foreach($rowset as $id){

                $data[] = $id['lug_in_cod'];

            }

        }else{

            $data[] = 'null';

        }

        return $data;

    }



    public function listarTipo()

    {

        $adapter=$this->getAdapter();

        $sql=new Sql($adapter);

        $select = $sql->select();

        $select->from('tipo');

        $select->columns(array("id"=>"tip_in_cod","descripcion"=>"tip_vc_des"));

        $selectString=$sql->buildSqlString($select);

        return $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

    }


    public function listarUbigeo($params)

    {

        $page = $params['page'];

        $limit= $params['rows']; 

        $sidx = $params['sidx'];

        $sord = $params['sord'];

        $filtro = $params['filtro'];

    

        $adapter=$this->getAdapter();



        if($sidx=="id")$sidx="dis_in_cod";

        if($sidx=="departamento")$sidx="dep_vc_nom";

        if($sidx=="provincia")$sidx="prv_vc_nom";

        if($sidx=="distrito")$sidx="dis_vc_nom";



        $tDistritos = "SELECT COUNT(*) as totalrows from distrito limit 5";



        if($filtro!="null"){

            $tDistritos="SELECT COUNT(*) as totalrows from distrito where dis_vc_nom like '$filtro%'";

        }



        $totalDistrito = $adapter->query($tDistritos,$adapter::QUERY_MODE_EXECUTE)->toArray();

        $count = $totalDistrito[0]['totalrows'];



        if( $count >0 ) {

           $total_pages = ceil($count/$limit);

        } else {

           $total_pages = 0;

        }



        if ($page > $total_pages)

            $page=$total_pages;

       

        if($page==0){

            $page=1;

        }



        $start = $limit*$page - $limit;



        $paginacion="LIMIT $start , $limit";



        if($count<=0){

           $paginacion="";

        }



        $queryUbigeo="SELECT dep_vc_nom,prv_vc_nom,dis_in_cod, dis_vc_nom FROM distrito dis 

        inner join provincia pro on dis.prv_in_cod=pro.prv_in_cod inner join departamento dep on pro.dep_in_cod=dep.dep_in_cod order by dis_vc_nom asc ".$paginacion;

        if($filtro!="null"){

           $queryUbigeo="SELECT dep_vc_nom,prv_vc_nom,dis_in_cod, dis_vc_nom FROM distrito dis inner join provincia pro on dis.prv_in_cod=pro.prv_in_cod inner join departamento dep on pro.dep_in_cod=dep.dep_in_cod  where dis_vc_nom like '$filtro%' order by $sidx $sord ".$paginacion; 

        }



        $result = $adapter->query($queryUbigeo,$adapter::QUERY_MODE_EXECUTE)->toArray();



        $responce=array();

        $responce['page'] = $page;

        $responce['total'] = $total_pages;

        $responce['records'] = $count;



        $i=0;



        foreach($result as $ubigeo) {

            $responce['rows'][$i]['id']=$ubigeo['dis_in_cod']; 

            $responce['rows'][$i]['cell']=array(

              $ubigeo['dep_vc_nom'],

              $ubigeo['prv_vc_nom'],

              $ubigeo['dis_vc_nom'],

            );                               

            $i++;

        }    



        return $responce;



    }

    private function saveAuditoria($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['idUsuario'], //idusuario
            'edi_in_cod'=> $params['edificioId'], //idedificio
            'aud_opcion'=> $params['ruta'], //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> 'Edificio', //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> '' //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

}