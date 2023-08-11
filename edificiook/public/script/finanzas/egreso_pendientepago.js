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
            postData:{query:'pendienteDePago'},
            colNames:['','','F. Emision','F. Vence','Proveedor','Concepto','Documento','Serie / N°','Importe','Nota'],
            colModel:[
                {name:'id',index:'id', width:100,sortable:false,formatter:GridEgreso.formatIcoButton},
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
    formatIcoButton:function(cellvalue,options,rowObject){
        if(cellvalue=='' || cellvalue == null){
            return "";
        }
        return '<button id="btn-changeStatus" id-egr="'+cellvalue+'" class="btn-changeStatus btn btn-default" style="border:0px!important;background: #C3695A!important;"><i class="fa fa-money"></i><span> Pagar</span></button>';
    }
}


//operaciones de la barra de botones
var Action={
    saveAction:'finanzas/egreso/save',
    readAction:'finanzas/egreso/read',
    deleteAction:'finanzas/egreso/delete',
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
            $("#formPagoEgreso #textImporte").val(data.totalDebe);
            if(data.totalDebe=='0.00'){
                $("#wrapper_btnViewFormPago").addClass('hidden');
            }
            if(data.rowsPagos.length>0){
                $('#pageTab_grid').hide();
                $('#pageTab_detalle').show();
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
        $("#formEgreso input[type=text], #formEgreso select, #formEgreso textarea").attr('disabled',true);
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

        $.post(this.readAction,{
            query:'detallepago',
            egresoId:egresoId
        },function(data){
            $("#mpe_tab1descripcion").html('debe <b>'+data.totalDebe+"</b> de <b>"+data.total+"</b>");
            $("#mpe_tab2descripcion").html('pagado <b>'+data.totalPagado+'</b> de <b>'+ data.total+"</b>");
            $("#mpe_textImporte").val(data.totalDebe);

            console.log(data.rowsPagos.length);
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
               
                $('#tab_listarpagos').removeClass('hidden');
            }else{
                $('#tab_listarpagos').addClass('hidden');
                $('#tab_registrarpago a:first').tab('show');
                $("#textNroOperacion").focus();
                $("#mpe_listaEgresosParciales tbody").html('');
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
                    GridEgreso.reloadGrid();
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
                    //GridEgreso.reloadGrid();
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar eliminar el pago','error');
        });
    },
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


$(".precio").keypress(function(e){
    if(e.which == 0) return true;
    if(e.which == 8) return true;
    if(e.which == 45) return true;                
    if(e.which < 46) return false;
    if(e.which > 46 && e.which<48) return false;
    if(e.which > 57 ) return false;
});

$('#textImporte').priceFormat({
    prefix: '',
    thousandsSeparator: ''
});

//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridEgreso.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridEgreso.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-300);
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



$(GridEgreso.gridId).on('click','.btn-changeStatus',function(){
    myfunction=$(this).attr('fn');
    idCurrentEgreso=$(this).attr('id-egr');
    $("#co_egresoId").val(idCurrentEgreso);

    var rowData = $(GridEgreso.gridId).getRowData(idCurrentEgreso);
    var colDataProveedor = rowData['proveedor'];
    var colDataConcepto = rowData['concepto']; 
    var colDataNota=rowData['nota'];
    
    var valueTitleModal='<span>Egreso </span><small><i class="ace-icon fa fa-angle-double-right"></i> '+colDataConcepto
                        +' <i class="ace-icon fa fa-angle-double-right"></i> '+colDataProveedor+'</small>';
    
    $("#modalPagoEgreso .modal-title").html(valueTitleModal);
    $("#labelNotaIngreso").html(' '+colDataNota);
    

    Action.viewModalPagar();
});


$("#mpe_listaEgresosParciales tbody").on('click','.btn_deletePago',function(){
    var idEgrParcial=$(this).attr('btn-id');
    $("#co_idPagoDelete_mep").val(idEgrParcial);
    $("#modalConfirmacionEliminarEP_mep").modal('show');
    $("#modalConfirmacionEliminarEP_mep .modal-dialog").css({'width':'30%','margin-top':'10%'});
});