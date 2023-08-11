var GridEgreso={

    egresoController:"finanzas/egreso/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",
    listMeses:['','enero','febrero','marzo','abril','mayo','junio','julio','Agosto','septiembre','octubre','noviembre','diciembre'],

    init:function(){
        $(this.gridId).jqGrid({
            url: this.egresoController,
            datatype: "json",
            mtype: "POST",
            colNames:["Descripción","Ene",'Feb',"Mar","Abr","May",'Jun','Jul','Ago','Sep','Oct','Nov','Dic','T. Anual'],
            colModel:[
                    {name:'descripcion',index:'descripcion', width:300},
                    {name:'ene',index:'ene', width:100,align:"right",classes:'col-mes'},
                    {name:'feb',index:'feb', width:100,align:"right",classes:'col-mes'},
                    {name:'mar',index:'mar', width:100,align:"right",classes:'col-mes'},
                    {name:'abr',index:'abr', width:100,align:"right",classes:'col-mes'},
                    {name:'may',index:'may', width:100,align:"right",classes:'col-mes'},
                    {name:'jun',index:'jun', width:100,align:"right",classes:'col-mes'},
                    {name:'jul',index:'jul', width:100,align:"right",classes:'col-mes'},
                    {name:'ago',index:'ago', width:100,align:"right",classes:'col-mes'},
                    {name:'sep',index:'sep', width:100,align:"right",classes:'col-mes'},
                    {name:'oct',index:'oct', width:100,align:"right",classes:'col-mes'},
                    {name:'nov',index:'nov', width:100,align:"right",classes:'col-mes'},
                    {name:'dic',index:'dic', width:100,align:"right",classes:'col-mes'},
                    {name:'total',index:'total', width:110,align:"right",},
            ],
            shrinkToFit: false,
            treeGrid:true,
            treeGridModel: 'adjacency',
            ExpandColumn: 'descripcion',
            height:'100%',
            ExpandColClick: true,
            pager : this.pageGridId,
            viewrecords: true,
            ondblClickRow:function(rowId, iRow, iCol, e){
                if(rowId=='TOTAL_BANCO'){
                    GridBanco.reloadGrid({'mes':(iCol),'year':DateControls.yearSelected});
                    var titleListaBanco=GridEgreso.listMeses[iCol]+" del "+DateControls.yearSelected;
                    $("#pageTab_detalleBanco .label-title").html(titleListaBanco);
                    $("#pageTab_grid").hide();
                    $("#pageTab_detalleBanco").show();
                }
            },
            loadComplete:function(){
                setTimeout(function(){
                    GridEgreso.updatePagerIcons(this.gridId);
                },0)
            },
            'treeIcons':{
                plus:'ace-icon fa fa-plus center bigger-110 blue',
                minus:'ace-icon fa fa-minus center bigger-110 blue',
                leaf:'ace-icon fa fa-chevron-right center orange'
            },
        });

        //$(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false});
        $(this.gridId).jqGrid('navGrid','#grid-pager',{edit:false,add:false,del:false});
    },
    updatePagerIcons:function(table){
        var replacement = 
        {
            'ui-icon-seek-first' : 'ace-icon fa fa-angle-double-left bigger-140',
            'ui-icon-seek-prev' : 'ace-icon fa fa-angle-left bigger-140',
            'ui-icon-seek-next' : 'ace-icon fa fa-angle-right bigger-140',
            'ui-icon-seek-end' : 'ace-icon fa fa-angle-double-right bigger-140'
        };
        $('.ui-pg-table:not(.navtable) > tbody > tr > .ui-pg-button > .ui-icon').each(function(){
            var icon = $(this);
            var $class = $.trim(icon.attr('class').replace('ui-icon', ''));
            if($class in replacement) icon.attr('class', 'ui-icon '+replacement[$class]);
        })
    
    },

    reloadGrid:function(arrayParams){
        $(this.gridId).setGridParam(
            { 
                url: this.egresoController, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
    }
}


var GridBanco={

    egresoController:"finanzas/egreso/read",
    gridId:"#grid-tableBanco",
    pageGridId:"#grid-pagerBanco",

    init:function(){
        
        $(this.gridId).jqGrid({
            url: this.egresoController,
            datatype: "json",
            mtype: "POST",
            postData: {'query':'egresoParcialParaGridBanco'},
            colNames:["F. Pago","Proveedor",'N° Operación',"Banco","Importe",'T. documento','Serie','N° Doc','F. Emisión'],
            colModel:[
                    {name:'fechapago',index:'fechapago', width:70},
                    {name:'proveedor',index:'proveedor', width:200,},
                    {name:'nrooperacion',index:'nrooperacion', width:100,align:"right"},
                    {name:'banco',index:'banco', width:250},
                    {name:'importe',index:'importe', width:120,align:"right"},
                    {name:'tipodoc',index:'tipodoc', width:100,classes:'col-egrban'},
                    {name:'serie',index:'serie', width:100,align:"right",classes:'col-egrban'},
                    {name:'nrodoc',index:'nrodoc', width:100,align:"right",classes:'col-egrban'},
                    {name:'fechaemision',index:'fechaemision', width:100,align:"right",classes:'col-egrban'},
            ],
            rowNum:1000,
            shrinkToFit: false,
            height:'100%',
            pager : this.pageGridId,
            viewrecords: true,
            footerrow : true,
            userDataOnFooter : true,
        });

        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false});
        $(this.gridId).jqGrid('navGrid','#grid-pagerBanco',{edit:false,add:false,del:false});
    },
    reloadGrid:function(arrayParams){
        $(this.gridId).setGridParam(
            { 
                url: this.egresoController, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
    }

}

GridBanco.init();

//operaciones de la barra de botones
var Action={
    saveAction:'finanzas/egreso/save',
    deleteAction:'finanzas/egreso/delete',
    readAction:'finanzas/egreso/read',
    //sestiloEstadoEgreso={0:'egr-pagado',1:'egr-pendienteAprobacion',2:'egr-pendientePago',3:'egr-provisionado'},
    loadDatosEgreso:function(egresoId){
        if(egresoId==undefined){
           egresoId=$("#co_egresoId").val(); 
        }
        $.post(this.readAction,{
            query:'dataegreso',
            egresoId:egresoId
        },function(data){
            $("#co_egresoId").val(egresoId);
            $("#selTipoDoc").val(data.tipoDoc);
            $("#selTipoDoc").trigger('chosen:updated');

            $("#textSerie").val(data.serie);
            $("#textNroDocumento").val(data.nroDoc);
            $("#textFechaEmi").val(data.fechaEmision);
            $("#textFechaVence").val(data.fechaVence);
            $("#selProveedor").val(data.proveedorId);
            $("#selProveedor").trigger('chosen:updated');
            $("#selConcepto").val(data.conceptoId);
            $("#selConcepto").trigger('chosen:updated');
            $("#textImporte").val(data.importe);
            
            $("#ta_nota").val(data.observacion);

            if(data.conceptoId==25){
              $("#wrapper_metroscubicos").removeClass('hidden');
              $("#textM3").val(data.metroscubicos);
            }else{
                $("#wrapper_metroscubicos").addClass('hidden');
                $("#textM3").val('');
            }

            $("#estado_recibo_digital").val('0');
            if(data.file==true){
                $("#input_recibo_digital").addClass('hidden');
                $("#preview_recibo_digital").removeClass('hidden'); 
                $("#preview_recibo_digital a").attr('href',$("base").attr('href')+'file/egreso/'+data.ruta_file_digital);
            }else{
                $("#input_recibo_digital").removeClass('hidden');
                $("#preview_recibo_digital").addClass('hidden');
            }
            $('#file_recibo_digital').ace_file_input('reset_input');


        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar recuperar los datos de egreso','error');
        });
    },
    viewDetalle:function(conceptoId,proveedorId,mes){
        urlbase=$("base").attr('href');
        $("#dte_co_proveedorId").val(proveedorId);
        $("#dte_co_conceptoId").val(conceptoId);
        $("#dte_co_mes").val(mes);

        $.post(this.readAction,{
            query:'egresosPorConceptoAndProveedor',
            conceptoId:conceptoId,
            proveedorId:proveedorId,
            mes:mes,
            year:DateControls.yearSelected
        },function(data){
            $("#textConceptoNombre").val('');
            $("#textProveedorRZ").val('');

            if(data.rows.length>0){
                $('#pageTab_grid').hide();
                $('#pageTab_detalle').show();
                $("#btn-actions-detalleEgr").addClass('hidden');
                $("#textConceptoNombre").val(data.concepto);
                $("#textProveedorRZ").val(data.proveedor);

                var valueTitleModal='<span>Egreso </span><small><i class="ace-icon fa fa-angle-double-right"></i> '+data.concepto+' '
                                +'<i class="ace-icon fa fa-angle-double-right"></i> '+data.proveedor+'</small>';
                
                $("#labelModalEgreso").html(valueTitleModal);
                $("#labelModalEgreso_pe").html(valueTitleModal);
                $("#labelModalEgreso_lpe").html(valueTitleModal);

                var contentTable='';
                $.each(data.rows,function(key,value){

                    var botonLista='';
                    var egresoStatus=value['estado'];
                    if(egresoStatus=='0'){
                        botonLista='<button id="btn-changeStatus" status="'+egresoStatus+'" id-egr="'+value['id']+'" type="button" class="btn-changeStatus btn btn-default" style="border:0px!important;background: #4E9E54!important;"><i class="fa fa-check"></i><span> Pagado</span></button>';
                    }
                    if(egresoStatus=='1'){
                        botonLista='<button id="btn-changeStatus" status="'+egresoStatus+'" id-egr="'+value['id']+'" type="button" class="btn-changeStatus btn btn-default" style="border:0px!important;background: #428bca!important;"><i class="fa fa-send"></i><span> Aprobar</span></button>';
                    }
                    if(egresoStatus=='2'){
                        botonLista='<button id="btn-changeStatus" status="'+egresoStatus+'" id-egr="'+value['id']+'" class="btn-changeStatus btn btn-default" style="border:0px!important;background: #C3695A!important;"><i class="fa fa-money"></i><span> Pagar</span></button>';
                    }
                    if(egresoStatus=='3'){
                        botonLista='<button id="btn-changeStatus" status="'+egresoStatus+'" id-egr="'+value['id']+'"  type="button" class="btn-changeStatus btn btn-default" style="border:0px!important"><i class="fa fa-play"></i><span> Procesar</span></button>';
                    }

                    //enlace recibo digital
                    var adjunto=""
                    if(value['adjunto']=='' || value['adjunto'] == null){
                        adjunto="";
                    }else{
                       adjunto='<a href="'+urlbase+'file/egreso/'+value['adjunto']+'" target="_blank" ><img width="27" height="25" src="'+urlbase+'images/iconos/pdf.png'+'" alt=""></a>'; 
                    }
                    

                    nota='<td></td>';
                    if(value['nota']!=''){
                        nota='<td><span class="ace-icon fa fa-comment blue bigger-130" data-rel="popover" data-trigger="hover" data-placement="left" data-content="More details." title="'+value['nota']+'"></span></td>';
                    }
                    contentTable+="<tr>"
                        +"<td>"
                            +"<label class='position-relative tooltipmsg'>"
                                +"<input id='chkCol"+value['id']+"' type='checkbox' class='ace' value='"+value['id']+"' onclick='Action.checkBoxSelected("+value['id']+")' egr-state='"+value['estado']+"'>"
                                +"<span class='lbl'></span>"
                            +"</label>"
                        +"</td>"
                        +"<td>"+botonLista+"</td>"
                        +"<td>"+value['fechaEmi']+"</td>"
                        +"<td>"+value['fechaVence']+"</td>"
                        +"<td>"+value['tipoDoc']+"</td>"
                        +"<td>"+value['nroDoc']+"</td>"
                        +"<td align='right'>"+value['importe']+"</td>"
                        +"<td>"+adjunto+"</td>"+nota
                        +"</tr>";
                });
                $("#tblEgresoPorConceptoAndProveedor tbody").html(contentTable);
            }else{

            }

        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar recuperar el detalle de egresos','error');
        });
    },
    checkBoxSelected:function(id){
        inputSelectedId = 'chkCol'+id;
        if($("#"+inputSelectedId).is(':checked')){
            $("#tblEgresoPorConceptoAndProveedor input[type='checkbox']").each(function(index,value){
                idCurrentInput=$(value).attr('id');
                $("#co_egresoId").val(id);
                if(idCurrentInput != inputSelectedId){
                    $("#"+idCurrentInput).attr('checked', false);
                }else{
                    $("#formEgreso input:text,#formEgreso select").attr('disabled','true');
                    $("#btn-actions-detalleEgr").removeClass('hidden');
                }
            });
        }else{
            $("#btn-actions-detalleEgr").addClass('hidden');
            $("#formEgreso")[0].reset();
        }
    },
    new:function(){
        var validator = $("#formEgreso").validate();
        validator.resetForm();
        $("#formEgreso .form-group").removeClass('has-error');
        $("#chkValidarDoc").removeClass('hidden');
        $("#modalEgreso h4.modal-title span").html('Nuevo Egreso');
        $("#modalEgreso").modal('show');
        $(".modal-dialog").css({'width':'70%'});
        $("#wrapper_metroscubicos").addClass('hidden');
        $("#input_recibo_digital").removeClass('hidden');
        $("#preview_recibo_digital").addClass('hidden');
        
        //limpiar controles.
        $("#formEgreso")[0].reset();
        $('#file_recibo_digital').ace_file_input('reset_input');
        $("#selTipoDoc").val(0);
        $("#selTipoDoc").trigger('chosen:updated');
        $("#selProveedor").val(0);
        $("#selProveedor").trigger('chosen:updated');
        $("#selConcepto").val(0);
        $("#selConcepto").trigger('chosen:updated');
        egresoId=$("#co_egresoId").val('0');
        $("#estado_recibo_digital").val('0');
        $("#btnSaveEgreso").removeClass('hidden');

        $("#formEgreso input[type=text], #formEgreso select, #formEgreso textarea").attr('disabled',false);
        $("#remove_preview_recibo_digital").removeClass('hidden');
    },
    edit:function(){
        var validator = $("#formEgreso").validate();
        validator.resetForm();
        $("#chkValidarDoc").addClass('hidden');
        $("#formEgreso .form-group").removeClass('has-error');
        $("#modalEgreso h4.modal-title span").html('Editar Egreso');
        Action.loadDatosEgreso();
        $("#modalEgreso").modal('show');
        $(".modal-dialog").css({'width':'70%'});
        $("#btnSaveEgreso").removeClass('hidden');
        $("#formEgreso input[type=text], #formEgreso select, #formEgreso textarea").attr('disabled',false);
        $("#remove_preview_recibo_digital").removeClass('hidden');
    },
    delete:function(){
        var egresoId=$("#co_egresoId").val();
        formData=new Array();
        formData.push({name:'id',value:egresoId});
        formData.push({name:'query',value:'deleteEgreso'});
        $("#modalConfirmacionEliminarEgreso").modal('hide');
        $.post(this.deleteAction,formData,function(data){
            if(data.tipo){
                alerta('Egreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    GridEgreso.reloadGrid({year:DateControls.year});
                    Action.viewDetalle($("#dte_co_conceptoId").val(),$("#dte_co_proveedorId").val(),$("#dte_co_mes").val());
                    $("#btn-actions-detalleEgr").addClass('hidden');
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el egreso','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el egreso','error');
        });
    },
    saveEgreso:function(){
        $("#btnSaveEgreso").prop("disabled", true );
        var formDataImg = new FormData($("#formEgreso")[0]);
        $.ajax({
            type: "POST",
            dataType:'json',
            url: this.saveAction,
            data: formDataImg,
            contentType:false,
            processData:false,
            success: function(data){
                if(data.tipo){
                    alerta('Egreso',data.mensaje,data.tipo);
                    if(data.tipo=='informativo'){
                        GridEgreso.reloadGrid({year:DateControls.year});
                        Action.viewDetalle($("#dte_co_conceptoId").val(),$("#dte_co_proveedorId").val(),$("#dte_co_mes").val());
                        $("#modalEgreso").modal('hide');
                        $("#formEgreso")[0].reset();
                    }
                }
                $("#btnSaveEgreso").removeAttr("disabled");
            },
            error: function (xhr, ajaxOptions, thrownError) {
                $("#btnSaveEgreso").removeAttr("disabled");
                alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar guardar los datos','error');
            }
        });
    },
    procesar:function(egresoId){
        if(egresoId==undefined){
           egresoId=$("#co_egresoId").val(); 
        }
        $("#modalConfirmacionProcesar").modal('hide');

        $.post(this.saveAction,{
            query:'procesarEgreso',
            egresoId:egresoId
        },function(data){
            if(data.tipo){
                alerta('Egreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    Action.viewDetalle($("#dte_co_conceptoId").val(),$("#dte_co_proveedorId").val(),$("#dte_co_mes").val());
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar procesar el egreso seleccionado','error'); 
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar procesar el egreso seleccionado','error');
        });
    },
    aprobar:function(egresoId){
        if(egresoId==undefined){
           egresoId=$("#co_egresoId").val(); 
        }
        $("#modalConfirmacionAprobar").modal('hide');

        $.post(this.saveAction,{
            query:'aprobarEgreso',
            egresoId:egresoId
        },function(data){
            if(data.tipo){
                alerta('Egreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    Action.viewDetalle($("#dte_co_conceptoId").val(),$("#dte_co_proveedorId").val(),$("#dte_co_mes").val());
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar aprobar el egreso seleccionado','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar aprobar el egreso seleccionado','error');
        });
    },
    
    viewModalPagar:function(){
        $("#modalPagoEgreso").modal('show');
        $(".modal-dialog").css({'width':'70%'});
        Action.loadDataModalPagar();

    },

    loadDataModalPagar:function(egresoId){
        if(egresoId==undefined){
           egresoId=$("#co_egresoId").val(); 
        }
       
        var validator = $("#formPagoEgreso").validate();
            validator.resetForm();
        $("#formPagoEgreso")[0].reset();
        $("#formPagoEgreso .form-group").removeClass('has-error');
        $('#wrapper_btnViewFormPago').removeClass('hidden');

        $.post(this.readAction,{
            query:'detallepago',
            egresoId:egresoId
        },function(data){
            $("#mpe_tab1descripcion").html('debe <b>'+data.totalDebe+"</b> de <b>"+data.total+"</b>");
            $("#mpe_tab2descripcion").html('pagado <b>'+data.totalPagado+'</b> de <b>'+ data.total+"</b>");
            $("#mpe_textImporte").val(data.totalDebe);

            if(data.rowsPagos.length>0){
                var contentTable='';
                $.each(data.rowsPagos,function(key,value){
                    var nota='';
                    if(value['nota']!=''){
                        nota='<span class="ace-icon fa fa-comment blue bigger-130" data-rel="popover" data-trigger="hover" data-placement="left" data-content="More details." title="'+value['nota']+'"></span>';
                    }
                    contentTable+="<tr>"
                        +"<td><button class='btn btn-default btn_deletePago' btn-id='"+value['id']+"' type='button'><i class='glyphicon glyphicon-trash'></i></button></td>"
                        +"<td>"+value['fechaPago']+"</td>"
                        +"<td>"+value['banco']+"</td>"
                        +"<td>"+value['nroOperacion']+"</td>"
                        +"<td align='right'>"+value['importe']+"</td>"
                        +"<td>"+nota+"</td>"
                    +"</tr>";

                });
                $("#mpe_listaEgresosParciales tbody").html(contentTable);
                $(".msg_listaPagos").addClass('hidden');
                $('#tab_listarpagos').removeClass('hidden');
            }else{
                $('.nav-tabs a[href="#home"]').tab('show');
                $("#mpe_listaEgresosParciales tbody").html('');
                $(".msg_listaPagos").removeClass('hidden');
                $('#tab_listarpagos').addClass('hidden');
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar recuperar datos de egreso','error');
        });
    },

    savePago:function(){
        formData=$("#formPagoEgreso").serializeArray();
        var egresoId=$("#co_egresoId").val();
        formData.push({name:'egresoId',value:egresoId});
        formData.push({name:'query',value:'registrarPago'});

        $.post(this.saveAction,formData,function(data){
            if(data.tipo){
                alerta('Egreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    var validator = $("#formPagoEgreso").validate();
                    validator.resetForm();
                    $("#formPagoEgreso .form-group").removeClass('has-error');
                    $("#modalPagoEgreso").modal('hide');
                    Action.viewDetalle($("#dte_co_conceptoId").val(),$("#dte_co_proveedorId").val(),$("#dte_co_mes").val());
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar registrar un pago','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar registrar un pago','error');
        });
    },
    deleteEP_mep:function(){
        formData=new Array();
        var egresoId=$("#co_egresoId").val();
        var idEgrParcial=$("#co_idPagoDelete_mep").val();
        formData.push({name:'id',value:idEgrParcial})
        formData.push({name:'egresoId',value:egresoId});
        formData.push({name:'query',value:'deleteEP'});

        $.post(this.deleteAction,formData,function(data){
            if(data.tipo){
                alerta('Egreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                
                    $("#modalConfirmacionEliminarEP_mep").modal('hide');
                    Action.loadDataModalPagar(egresoId);
                    Action.viewDetalle($("#dte_co_conceptoId").val(),$("#dte_co_proveedorId").val(),$("#dte_co_mes").val());
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');
        });
    },

    viewModalListaDePagos:function(){
        $("#modalListaPagosEgreso").modal('show');
        $(".modal-dialog").css({'width':'70%'});
        Action.loadDataListaDePagos();
    },
    
    loadDataListaDePagos:function(egresoId){
        if(egresoId==undefined){
           egresoId=$("#co_egresoId").val(); 
        }
        $.post(this.readAction,{
            query:'detallepago',
            egresoId:egresoId
        },function(data){
            $("#lbTotalEgreso span").html(data.total);
            $("#lbTotalPagado span").html(data.totalPagado)
            $("#lbTotalDebe span").html(data.totalDebe);
            $("#textImporte").val(data.totalDebe);
            if(data.totalDebe=='0.00'){
                $("#wrapper_btnViewFormPago").addClass('hidden');
            }
            if(data.rowsPagos.length>0){
                var contentTable='';
                $.each(data.rowsPagos,function(key,value){
                    nota='<td></td>';
                    if(value['nota']!=''){
                        nota='<td><span class="ace-icon fa fa-comment blue bigger-130" data-rel="popover" data-trigger="hover" data-placement="left" data-content="More details." title="'+value['nota']+'"></span></td>';
                    }
                    contentTable+="<tr>"
                        +"<td><button class='btn btn-default btn_deletePago' btn-id='"+value['id']+"' type='button'><i class='glyphicon glyphicon-trash'></i></button></td>"
                        +"<td>"+value['fechaPago']+"</td>"
                        +"<td>"+value['banco']+"</td>"
                        +"<td>"+value['nroOperacion']+"</td>"
                        +"<td align='right'>"+value['importe']+"</td>"+nota
                    +"</tr>";

                });
                $("#tblEgresosParciales tbody").html(contentTable);
                $(".msg_listaPagos").addClass('hidden');
            }else{
                $(".msg_listaPagos").removeClass('hidden');
            }

        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar recuperar los datos de pago','error');
        });
    },
    deleteEP:function(){
        formData=new Array();
        var egresoId=$("#co_egresoId").val();
        var idEgrParcial=$("#co_idPagoDelete").val();
        formData.push({name:'id',value:idEgrParcial})
        formData.push({name:'egresoId',value:egresoId});
        formData.push({name:'query',value:'deleteEP'});

        $.post(this.deleteAction,formData,function(data){
            if(data.tipo){
                alerta('Egreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    
                    //cerramos cualquier de los 2 modal que utlizan esta funcion.
                    $("#modalListaPagosEgreso").modal('hide');
                    $("#modalPagoEgreso").modal('hide');

                    $("#modalConfirmacionEliminarEP").modal('hide');
                    Action.loadDataListaDePagos(egresoId);
                    Action.viewDetalle($("#dte_co_conceptoId").val(),$("#dte_co_proveedorId").val(),$("#dte_co_mes").val());
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');
        });
    }
}

var DateControls={
    yearSelected:$("#currentYear").html(),
    nextYear:function(){
        this.yearSelected++;
        $("#currentYear").html(this.yearSelected);
        GridEgreso.reloadGrid({year:this.yearSelected});
    },
    backYear:function(){
        this.yearSelected--;
        $("#currentYear").html(this.yearSelected);
        GridEgreso.reloadGrid({year:this.yearSelected});
    },
}

var Options={
    conceptoAction:'finanzas/presupuesto/concepto',
    lastValue:$('#selTipo').val(),
    listarConceptos:function(){
        $.post(this.conceptoAction,{
            tipo:this.lastValue
        },function(){

        },'json').fail(function(){
            alert("error petición");
        });
    }
}


GridEgreso.init();

$('.datepicker-right').datepicker({
    'language': 'es',
    autoclose: true,
    todayHighlight: true,
    format:'dd-mm-yyyy',
    orientation: 'right buttom'
});

$('.datepicker-right').on('show', function(e){
    //console.debug('show', e.date, $(this).data('stickyDate'));
    
    if ( e.date ) {
         $(this).data('stickyDate', e.date);
    }
    else {
         $(this).data('stickyDate', null);
    }
});

$('.datepicker-right').on('hide', function(e){
    //console.debug('hide', e.date, $(this).data('stickyDate'));
    var stickyDate = $(this).data('stickyDate');
    
    if ( !e.date && stickyDate ) {
        console.debug('restore stickyDate', stickyDate);
        $(this).datepicker('setDate', stickyDate);
        $(this).data('stickyDate', null);
    }
});


$('.datepicker-default').datepicker({
    'language': 'es',
    autoclose: true,
    todayHighlight: true,
    format:'dd-mm-yyyy',
});

$('.datepicker-default').on('show', function(e){
    //console.debug('show', e.date, $(this).data('stickyDate'));
    
    if ( e.date ) {
         $(this).data('stickyDate', e.date);
    }
    else {
         $(this).data('stickyDate', null);
    }
});

$('.datepicker-default').on('hide', function(e){
    //console.debug('hide', e.date, $(this).data('stickyDate'));
    var stickyDate = $(this).data('stickyDate');
    
    if ( !e.date && stickyDate ) {
        console.debug('restore stickyDate', stickyDate);
        $(this).datepicker('setDate', stickyDate);
        $(this).data('stickyDate', null);
    }
});


$('.fecha').mask('99-99-9999').val('dd-mm-aaaa');

$(".precio").keypress(function(e){
    if(e.which == 0) return true;
    if(e.which == 8) return true;
    if(e.which == 45) return true;                
    if(e.which < 46) return false;
    if(e.which > 46 && e.which<48) return false;
    if(e.which > 57 ) return false;
});

//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridEgreso.gridId).jqGrid( 'setGridWidth', $(".page-content").width()-10);
    $(GridEgreso.gridId).parents(".ui-jqgrid-bdiv").css('max-height', 10000);

    $(GridBanco.gridId).jqGrid( 'setGridWidth', $(".page-content").width()-5);
    $(GridBanco.gridId).parents(".ui-jqgrid-bdiv").css({'max-height':'10000px','overflow-y':'hidden'});

    $("div.page-content").css('max-height',$(window).height()-100);

})

//resize on sidebar collapse/expand
var parent_column = $(GridEgreso.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
        setTimeout(function(){
            $(GridEgreso.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});
$(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size

$("#btn-closeDetalle").click(function(){
    $("#pageTab_detalle").hide();
    $("#pageTab_grid").show();
});

$("#tblEgresoPorConceptoAndProveedor").on('click','.btn-changeStatus',function(){
    myfunction=$(this).attr('fn');
    idCurrentEgreso=$(this).attr('id-egr');
    $("#co_egresoId").val(idCurrentEgreso);

    var egresoStatus=$(this).attr('status');
    if(egresoStatus=='0'){
        Action.viewModalListaDePagos();
    }
    if(egresoStatus=='1'){
        $("#modalConfirmacionAprobar").modal('show');
        $(".modal-dialog").css({'width':'30%'});
    }
    if(egresoStatus=='2'){
        Action.viewModalPagar();
    }
    if(egresoStatus=='3'){
        $("#modalConfirmacionProcesar").modal('show');
        $(".modal-dialog").css({'width':'30%'});     
    }

});


$("#btnSaveEgreso").click(function(e){
    $("#submit_SaveEgreso").click();
});

$ValidateNuevoIngreso = $("#formEgreso").validate({
    rules:{
        selTipoDoc : { required : true },
        textSerie: { required: true},
        textNroDocumento:{required:true},
        textFechaEmi:{required:true},
        textFechaVence:{required:true},
        selProveedor:{required:true},
        selConcepto:{required:true},
        textImporte:{required:true},
    },
    messages : {
        selTipoDoc:{required:'Seleccionar tipo de documento..'},
        textSerie:{required:'Ingresar N° de serie.'},
        textNroDocumento :{ required : 'Ingresar N° de documento.' },
        textFechaEmi :{required : 'Ingresar fecha de Emision.'},
        textFechaVence :{required : 'Ingresar fecha de Vencimiento.'},
        selProveedor:{required:'Seleccionar un proveedor'},
        selConcepto:{required:'Seleccionar un concepto'},
        textImporte:{required:'Ingresar importe S/.'},
    },
    highlight:function(e) {
        $(e).closest('.form-group').removeClass('has-info').addClass('has-error');
    },
    reset:function(form) {
        $(form).closest('.form-group').removeClass('has-error').addClass('has-info');
    },
    success: function (e) {
        $(e).closest('.form-group').removeClass('has-error');//.addClass('has-info');
        $(e).remove();
    },
    errorPlacement: function (error, element) {
        if(element.is('input[type=checkbox]') || element.is('input[type=radio]')) {
            var controls = element.closest('div[class*="col-"]');
            if(controls.find(':checkbox,:radio').length > 1) controls.append(error);
            else error.insertAfter(element.nextAll('.lbl:eq(0)').eq(0));
        }else if(element.is('.chosen-select')) {
            error.insertAfter(element.siblings('[class*="chosen-container"]:eq(0)'));
        }else{
            error.insertAfter(element.siblings('span'));
        }
    },
    submitHandler:function(){
        Action.saveEgreso();
    },
});

$("#selConcepto").on('change',function(){
    if($(this).val()==25){
      $("#wrapper_metroscubicos").removeClass('hidden');
      $("#textM3").val('');  
    }else{
        $("#wrapper_metroscubicos").addClass('hidden');
        $("#textM3").val(''); 
    }
});

//modalConfirmacionEliminarEgreso
$("#btn_deleteEgreso").click(function(){
    $("#modalConfirmacionEliminarEgreso ").modal('show');
    $("#modalConfirmacionEliminarEgreso .modal-dialog").css({'width':'30%'});
});


/*
* Validar pago parcial.
* */
$("#btnSavePagoEgreso").click(function(){
    $("#submitPagoEgreso").click();
});
var $ValidatePT = $("#formPagoEgreso").validate({
    rules : {
        textImporte : { required : true },
        selBanco : { required : true },
        textNroOperacion : { required : true },
        textFechaPago : { required : true },
    },
    messages : {
        textImporte : { required : 'Ingresar importe' },
        selBanco : { required : 'Seleccionar banco' },
        textNroOperacion : { required : 'Ingresar N° de operación' },
        textFechaPago : { required : 'Ingresar fecha de pago' },
    },
    highlight:function(e) {
        $(e).closest('.form-group').removeClass('has-info').addClass('has-error');
    },
    reset:function(form) {
        $(form).closest('.form-group').removeClass('has-error').addClass('has-info');
    },
    success: function (e) {
        $(e).closest('.form-group').removeClass('has-error');//.addClass('has-info');
        $(e).remove();
    },
    errorPlacement: function (error, element) {
        if(element.is('input[type=checkbox]') || element.is('input[type=radio]')) {
            var controls = element.closest('div[class*="col-"]');
            if(controls.find(':checkbox,:radio').length > 1) controls.append(error);
            else error.insertAfter(element.nextAll('.lbl:eq(0)').eq(0));
        }else if(element.is('.chosen-select')) {
            error.insertAfter(element.siblings('[class*="chosen-container"]:eq(0)'));
        }else{
            //error.insertAfter(element.parent());
            error.insertAfter(element.siblings());
        } 
    },
    submitHandler:function(){
        Action.savePago();
    },
});

$("#tblEgresosParciales tbody").on('click','.btn_deletePago',function(){
    var idEgrParcial=$(this).attr('btn-id');
    $("#co_idPagoDelete").val(idEgrParcial);
    $("#modalConfirmacionEliminarEP").modal('show');
    $("#modalConfirmacionEliminarEP .modal-dialog").css({'width':'30%','margin-top':'10%'});
});

$("#mpe_listaEgresosParciales tbody").on('click','.btn_deletePago',function(){
    var idEgrParcial=$(this).attr('btn-id');
    $("#co_idPagoDelete_mep").val(idEgrParcial);
    $("#modalConfirmacionEliminarEP_mep").modal('show');
    $("#modalConfirmacionEliminarEP_mep .modal-dialog").css({'width':'30%','margin-top':'10%'});
});

$("#remove_preview_recibo_digital").click(function(){
    $("#input_recibo_digital").removeClass("hidden");
    $("#preview_recibo_digital").addClass("hidden");
    $('#file_recibo_digital').ace_file_input('reset_input');
    $("#estado_recibo_digital").val('eliminar');
});
$('#file_recibo_digital').ace_file_input({
        no_file:'No hay archivo ...',
        btn_choose:'Explorar',
        btn_change:'Modificar',
        droppable:false,
        onchange:null,
        thumbnail:false, //| true | large
        whitelist:'pdf',
});


$("#btn-closeDetalleBanco").click(function(){
    $("#pageTab_grid").show();
    $("#pageTab_detalleBanco").hide();
});



$(".remove").click(function(e){
    $("#input_recibo_digital").removeClass("hidden");
    $("#preview_recibo_digital").addClass("hidden");
    $('#file_recibo_digital').ace_file_input('reset_input');
    $("#estado_recibo_digital").val('0');
    e.preventDefault();
});