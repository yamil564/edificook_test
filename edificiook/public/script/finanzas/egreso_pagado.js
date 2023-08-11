var GridEgreso={
    egresosController:"finanzas/egreso/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",
    init:function(){
        urlbase=$("base").attr('href');
        $(this.gridId).jqGrid({
            url:this.egresosController,
            datatype: "json",
            mtype: "POST",
            postData:{query:'pagado'},
            colNames:['','','F. Emision','F. Vence','Proveedor','Concepto','Documento','Serie / N°','Importe','Nota'],
            colModel:[
                {name:'id',index:'id', width:100,sortable:false,formatter:GridEgreso.formatButton},
                {name:'adjunto',index:'adjunto', width:40, sortable:false,formatter:GridEgreso.formatIcoPdf,search:false},
                {name:'fechaemi',index:'fechaemi', width:80},
                {name:'fechavence',index:'fechavence', width:80},
                {name:'proveedor',index:'proveedor', width:200,sortable:false},
                {name:'concepto',index:'concepto', width:200,sortable:false},
                {name:'documento',index:'documento', width:100, sortable:false},
                {name:'nrodoc',index:'nrodoc', width:70, sortable:false,align:"right"},
                {name:'importe',index:'importe', width:110,align:"right"},           
                {name:'nota',index:'nota', width:200, sortable:false,hidden:false,search:false}  
            ],
            shrinkToFit: false,
            height: '500',
            rowNum:20,
            rowList:[20,50,100],
            pager: this.pageGridId,
            sortname: 'id',
            recordpos: 'left',
            viewrecords: true,
            sortorder: "desc",
            loadComplete:function(data){
                if(data.rows){
                    $("#jqmsg_emptyrows").addClass('hidden');
                }else{
                    $("#jqmsg_emptyrows").removeClass('hidden');
                }
            },
            ondblClickRow:function(rowid,iRow, iCol, e){
                $("#co_egresoId").val(rowid); 
                Action.view();
            }
        });
        $(GridEgreso.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter: true});
    },
    reloadGrid:function(arrayParams){
        $(this.gridId).setGridParam(
            { 
                url: this.egresosController, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
    },
    formatIcoPdf:function(cellvalue,options,rowObject){
        if(cellvalue=='' || cellvalue == null){
            return "";
        }
        return '<a href="'+urlbase+'file/egreso/'+cellvalue+'" target="_blank" ><img width="27" height="25" src="'+urlbase+'images/iconos/pdf.png'+'" alt=""></a>';    
    },
    formatButton:function(cellvalue,options,rowObject){
        if(cellvalue=='' || cellvalue == null){
            return "";
        }
        return '<button id="btn-changeStatus" id-egr="'+cellvalue+'" type="button" class="btn-changeStatus btn btn-default" style="border:0px!important;background: #4E9E54!important;"><i class="fa fa-check"></i><span> Pagado</span></button>';
    },
    checkBoxSelected:function(id){
        inputSelectedId ='chkCol'+id;
        if($("#"+inputSelectedId).is(':checked')){
            $("#co_egresoId").val(id);
            $("input[type='checkbox']").each(function(index,value){
                idCurrentInput=$(value).attr('id');
                if(idCurrentInput != inputSelectedId){
                    $("#"+idCurrentInput).attr('checked', false);
                } else{
                    $("#btn-actions").removeClass('hidden');
                    $(".btn-actions-default").addClass('hidden');
                }
            });
        }else{
            $("#btn-actions").addClass('hidden');
            $(".btn-actions-default").removeClass('hidden');
        }
    }
}


//operaciones de la barra de botones
var Action={
    saveAction:'finanzas/egreso/save',
    deleteAction:'finanzas/egreso/delete',
    readAction:'finanzas/egreso/read',
    loadDatosEgreso:function(egresoId){
        if(egresoId==undefined){
           egresoId=$("#co_egresoId").val(); 
        }
        
        $.post(this.readAction,{
            query:'dataegreso',
            egresoId:egresoId
        },function(data){
            $("#co_egresoId").val(egresoId);
            $("#selTipoDoc").val(data.tipoDoc)
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

            $("#textRegistradoPor").val(data.registradoPor);
            $("#fechaRegistro").html(data.fechaRegistro);

            $("#textProcesadoPor").val(data.procesadoPor);
            $("#fechaProceso").html(data.fechaProceso);

            $("#textAprobadoPor").val(data.aprobadoPor);
            $("#fechaAprobacion").html(data.fechaAprobacion);

        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar recuperar los datos de egreso','error');
        });
    },
    loadDetallePago:function(egresoId){
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
                        +"<td>"+value['fechaPago']+"</td>"
                        +"<td>"+value['banco']+"</td>"
                        +"<td>"+value['nroOperacion']+"</td>"
                        +"<td align='right'>"+value['importe']+"</td>"+nota
                    +"</tr>";

                });
                $("#tblEgresosParciales tbody").html(contentTable);
                $("#msg_listaPagos").addClass('hidden');
            }else{
                $("#msg_listaPagos").removeClass('hidden');
            }

        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar recuperar los datos de pago','error');
        });
    },
    view:function(){
        Action.loadDatosEgreso();
        $("#modalEgreso").modal('show');
        $(".modal-dialog").css({'width':'70%'});
        $("#btnSaveEgreso").addClass('hidden');
        $("#formEgreso input[type=text], #formEgreso select, #formEgreso textarea").attr('disabled',true);
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
                    
                    //Cerramos cualquier de los 2 modal que utlizan esta funcion.
                    $("#modalListaPagosEgreso").modal('hide');
                    $("#modalPagoEgreso").modal('hide');

                    $("#modalConfirmacionEliminarEP").modal('hide');
                    Action.loadDataListaDePagos(egresoId);
                    GridEgreso.reloadGrid();
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');
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
    console.debug('hide', e.date, $(this).data('stickyDate'));
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
    console.debug('hide', e.date, $(this).data('stickyDate'));
    var stickyDate = $(this).data('stickyDate');
    
    if ( !e.date && stickyDate ) {
        console.debug('restore stickyDate', stickyDate);
        $(this).datepicker('setDate', stickyDate);
        $(this).data('stickyDate', null);
    }
});





$('.fecha').mask('99-99-9999').val('dd-mm-aaaa');

$('#textImporte').priceFormat({
    prefix: '',
    thousandsSeparator: ''
});

//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridEgreso.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridEgreso.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-250);
});

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


$('#file_recibo_digital').ace_file_input({
        no_file:'No hay archivo ...',
        btn_choose:'Explorar',
        btn_change:'Modificar',
        droppable:false,
        onchange:null,
        thumbnail:false, //| true | large
        whitelist:'pdf',
});

$(".ace-file-input a.remove").click(function(e){
    $('#file_recibo_digital').ace_file_input('reset_input');
    e.preventDefault();
});

$("#remove_preview_recibo_digital").click(function(){
    $("#input_recibo_digital").removeClass("hidden");
    $("#preview_recibo_digital").addClass("hidden");
    $('#file_recibo_digital').ace_file_input('reset_input');
    $("#estado_recibo_digital").val('eliminar');
});

$(GridEgreso.gridId).on('click','.btn-changeStatus',function(){
    myfunction=$(this).attr('fn');
    idCurrentEgreso=$(this).attr('id-egr');
    $("#co_egresoId").val(idCurrentEgreso);

    var rowData = $(GridEgreso.gridId).getRowData(idCurrentEgreso);
    var colDataProveedor = rowData['proveedor'];
    var colDataConcepto = rowData['concepto']; 
    
    var valueTitleModal='<span>Egreso </span><small><i class="ace-icon fa fa-angle-double-right"></i> '+colDataConcepto
                        +' <i class="ace-icon fa fa-angle-double-right"></i> '+colDataProveedor+'</small>';
    
    $("#modalListaPagosEgreso .modal-title").html(valueTitleModal);
    
    Action.viewModalListaDePagos();
});


$("#tblEgresosParciales tbody").on('click','.btn_deletePago',function(){
    var idEgrParcial=$(this).attr('btn-id');
    $("#co_idPagoDelete").val(idEgrParcial);
    $("#modalConfirmacionEliminarEP").modal('show');
    $("#modalConfirmacionEliminarEP .modal-dialog").css({'width':'30%','margin-top':'10%'});
});



