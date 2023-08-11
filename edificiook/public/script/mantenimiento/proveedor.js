var GridProveedor={
    gridAction:"mantenimiento/proveedor/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",
    currentRowIdSelected:null,
    init:function(){
        $(this.gridId).jqGrid({
            url: this.gridAction,
            datatype: "json",
            mtype: "POST",
            colNames:['',"Razón social","RUC"],
            colModel:[
                {name:'id',index:'id', width:50,frozen:true,'formatter':GridProveedor.formatChk,search:false,sortable:false},
                {name:'rz',index:'rz', width:200,frozen:true,search:true},
                {name:'documento',index:'documento', width:200,frozen:true,search:true},
            ],
            sortname: 'id',
            sortorder: "desc",

            shrinkToFit: true,
            rowList: [10,50,100],
            viewrecords: true,
            height: 'auto',
            rowNum: 10,
            pager : this.pageGridId,
            loadComplete:function () {
                /*$("#TOTAL_COBRADO, table tr#TOTAL_PENDIENTE, table tr#TOTAL_BANCO").addClass('hidden');
                buscar.unidad();*/
            }
        });

        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false});
    },
    reloadGrid:function(arrayParams){
        $(this.gridId).setGridParam(
            { 
                datatype: "json",
                url: this.gridAction, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
    },
    formatChk:function(cellvalue,options,rowObject){
        if(cellvalue==0 || cellvalue == null){
            return "";
        }
        return "<center><label class='position-relative tooltipmsg'><input type='checkbox' id='chkCol"+cellvalue+ "' name='chkCol"+cellvalue+"' class='ace' onclick='GridProveedor.checkBoxSelected("+cellvalue+");'/><span class='lbl'></span></label></center>";
    },
    checkBoxSelected:function(id){
        inputSelectedId ='chkCol'+id;
        if($("#"+inputSelectedId).is(':checked')){
            this.currentRowIdSelected=id;
            $("input[type='checkbox']").each(function(index,value){
                idCurrentInput=$(value).attr('id');
                if(idCurrentInput != inputSelectedId){
                    $("#"+idCurrentInput).attr('checked', false);
                } else{
                    $("#btn-actions").removeClass('hidden');
                    $("#btn-actions-default").addClass('hidden');
                }
            });
        }else{
            $("#btn-actions").addClass('hidden');
            $("#btn-actions-default").removeClass('hidden');
        }
    },
    formatearCelda:function(cellvalue,options,rowObject){
        if(cellvalue=='' || cellvalue == null){
            return "";
        }
        arrayValuesIngreso=cellvalue.split('|'); 
        //array arrayValuesIngreso es igual a {ingresoId,ingresoMes,ingresoEstado}
        if(arrayValuesIngreso.length==3){
            ingresoId=arrayValuesIngreso[0];
            ingresoMes=arrayValuesIngreso[1];
            ingresoEstado=arrayValuesIngreso[2];
            if(ingresoEstado==0){
                return "<span cell-id='"+ingresoId+"' class='pointer green'>"+ingresoMes+"</span>";
            }else if(ingresoEstado==2){
                return "<span cell-id='"+ingresoId+"' class='pointer orange'>"+ingresoMes+"</span>"
            }else{
                return "<span cell-id='"+ingresoId+"' class='pointer red'>"+ingresoMes+"</span>";
            } 
        }
        return cellvalue;     
    }
}

GridProveedor.init();

var buscar = {
    unidad:function()
    {
        var $content = $("#grid-table tbody tr .descripcion-unidad");
        $(".ui-search-toolbar").delegate("#gs_descripcion","keyup",function(){
            var keys = this.value.toString().toUpperCase();
            if (keys) {
                $content.filter(':contains('+keys+')').parent().show();
                $content.filter(':not(:contains('+keys+'))').parent().hide();
            } else {
                $content.parent().show();
            }
        });
    }
}




var Action= {
    saveAction: 'mantenimiento/proveedor/save',
    readAction: 'mantenimiento/proveedor/read',
    deleteAction: 'mantenimiento/proveedor/delete',
    ruta:$(".breadcrumb").text().trim(),
    new:function(){
        $("#form-nuevoProveedor")[0].reset();
        $("#modalNuevoProveedor").modal('show');
        $(".modal-dialog").css({'width': '50%', 'right': '0%'});
    },
    viewModalEditar:function(){
        $.post(this.readAction,{
            about:'getRowProveedor',
            id:GridProveedor.currentRowIdSelected,
        },function(data){
            $("input#frmEdit_textRazonSocial").val(data.razonsocial);
            $("input#frmEdit_textRuc").val(data.ruc);

            $("#modalEditarProveedor").modal('show');
            $(".modal-dialog").css({'width': '50%', 'right': '0%'});

        },'json').fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intertar recuperar datos del proveedor seleccionado.','error');
        });
    },
    saveProveedor:function() {
        var form=$("#form-nuevoProveedor").serializeArray();
        form.push({name:'about',value:'saveNewProveedor'});

        $.post(this.saveAction,form,function(data){
            if(data.tipo) {
                alerta('Proveedor', data.mensaje, data.tipo);

                if (data.tipo == 'informativo') {
                
                    GridProveedor.reloadGrid({page:1});
                    $("#form-nuevoProveedor")[0].reset();
                    $("#modalNuevoProveedor").modal('hide');
                }
            }else{
                alerta('Error','Ocurrió un problema en el servidor al intertar guardar los datos del nuevo proveedor.','error');
            }

        },'json')
        .fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intertar guardar los datos del nuevo proveedor.','error');
        });
    },
    saveEditarProveedor:function() {
        var form=$("#form-editarProveedor").serializeArray();
        form.push({name:'id',value:GridProveedor.currentRowIdSelected});
        form.push({name:'about',value:'saveEditProveedor'});
        $.post(this.saveAction,form,function(data){
            if(data.tipo) {
                alerta('Proveedor', data.mensaje, data.tipo);

                if (data.tipo == 'informativo') {
                    GridProveedor.currentRowIdSelected=null;
                    GridProveedor.reloadGrid({page:1});
                    $("#form-editarProveedor")[0].reset();
                    $("#modalEditarProveedor").modal('hide');
                }
            }else{
                alerta('Error','Ocurrió un problema en el servidor al intertar actualizar los datos del proveedor.','error');
            }

        },'json')
        .fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intertar actualizar los datos del proveedor.','error');
        });
    },
    confirmarDelete:function(){
        $("#modalConfirmarEliminacion").modal('show');
        $(".modal-dialog").css({'width':'30%'});
    },
    deteleProveedor:function(){
        if(GridProveedor.currentRowIdSelected==null){
            return;
        }
        
        $.post(this.deleteAction,{
            about:'deleteProveedor',
            id:GridProveedor.currentRowIdSelected,
        },function(data){
            if(data.tipo) {
                alerta('Usuarios', data.mensaje, data.tipo);

                if (data.tipo == 'informativo') {
                    GridProveedor.currentRowIdSelected=null;
                    GridProveedor.reloadGrid({page:1});
                    $("#modalConfirmarEliminacion").modal('hide');
                }
            }else{
                alerta('Error','Ocurrió un problema en el servidor al intentar eliminar el proveedor seleccionado.','error');
            }

        },'json')
        .fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intentar eliminar el proveedor seleccionado.','error');
        });
    },
}


// ventana concepto ingreso
$("#viewModalConceptos").click(function(e){
    e.preventDefault();
    Action.viewModalConceptos();
});


//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridProveedor.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridProveedor.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-270);
})

//resize on sidebar collapse/expand
var parent_column = $(GridProveedor.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout, solo para dar tiempo a los cambios del DOM y luego volver a dibujar el grid.
        setTimeout(function(){
            $(GridProveedor.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});
$(window).triggerHandler('resize.jqGrid');




//form add usuario

$("#btnSaveNuevo").click(function(){

    var error='';
    if($("#textRazonSocial").val()=='' ){
        $("#textRazonSocial").parent().addClass('has-error');
        error+=',textRazonSocial';
    }else {
        $("#textRazonSocial").parent().removeClass('has-error');
    }

    if(error!=''){
        return;
    }
    
    Action.saveProveedor();
});


//form edit usuario
$("#btnSaveEdit").click(function(){
    var error='';
    if($("input#frmEdit_textRazonSocial").val()=='' ){
        $("input#frmEdit_textRazonSocial").parent().addClass('has-error');
        error+=',frmEdit_textRazonSocial';
    }else {
        $("input#textRazonSocial").parent().removeClass('has-error');
    }

    if(error!=''){
        return;
    }

    Action.saveEditarProveedor();
});


$(".numero").keypress(function(e){
    if(e.which == 0) return true;
    if(e.which == 8) return true;
    if(e.which == 45) return true;                
    if(e.which < 46) return false;
    if(e.which > 46 && e.which<48) return false;
    if(e.which > 57 ) return false;
});
