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
            postData:{query:'pendienteDeAprobacion'},
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
            rowNum:50,
            rowList:[50,100],
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
    formatButton:function(cellvalue,options,rowObject){
        if(cellvalue=='' || cellvalue == null){
            return "";
        }
        return '<button id="btn-changeStatus" id-egr="'+cellvalue+'" type="button" class="btn-changeStatus btn btn-default" style="border:0px!important;background: #428bca!important;"><i class="fa fa-send"></i><span> Aprobar</span></button>';
    },
}


//operaciones de la barra de botones
var Action={
    saveAction:'finanzas/egreso/save',
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
    view:function(){
        Action.loadDatosEgreso();
        $("#modalEgreso").modal('show');
        $(".modal-dialog").css({'width':'70%'});
        $("#formEgreso input[type=text], #formEgreso select, #formEgreso textarea").attr('disabled',true);
    },
    aprobar:function(egresoId){
        if(egresoId==undefined){
           egresoId=$("#co_egresoId").val(); 
        }
        $.post(this.saveAction,{
            query:'aprobarEgreso',
            egresoId:egresoId
        },function(data){
            if(data.tipo){
                alerta('Egreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    GridEgreso.reloadGrid();
                    $("#modalConfirmacionAprobar").modal('hide');
                }
            }else{
              alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar aprobar el egreso seleccionado','error');  
            }
        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar aprobar el egreso seleccionado','error');
        });
    }
}

GridEgreso.init();

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


$(GridEgreso.gridId).on('click','.btn-changeStatus',function(){
    myfunction=$(this).attr('fn');
    idCurrentEgreso=$(this).attr('id-egr');
    $("#co_egresoId").val(idCurrentEgreso);

    $("#modalConfirmacionAprobar").modal('show');
    $(".modal-dialog").css({'width':'30%'});
});