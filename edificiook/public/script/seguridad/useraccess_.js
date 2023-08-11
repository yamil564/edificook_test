var GridUsuario={
    usuarioController:"seguridad/useraccess/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",
    checkboxSelectedAll:'<center><label class="position-relative tooltipmsg"><input type="checkbox" id="chkSelectAll" class="ace" /> <span class="lbl"></span></label></center>',
    init:function(){
        $(this.gridId).jqGrid({
            url: this.usuarioController,
            datatype: "json",
            mtype: "POST",
            colNames:['id',"Normbre","Correo","Ultimo Acceso"],
            colModel:[
                {name:'id',index:'id', width:50,frozen:true,search:false,sortable:false,hidden:true},
                {name:'nombre',index:'nombre', width:200,frozen:true,search:false},
                {name:'email',index:'email', width:200,frozen:true,search:false},
                {name:'ultimoacceso',index:'ultimoacceso', width:100,align:"right",sortable: false,search:false,'formatter':GridUsuario.formatearCelda,hidden:true},
            ],
            shrinkToFit: true,
            rowList: [],
            pgbuttons: false,
            pgtext: null,
            viewrecords: true,
            height: 'auto',
            loadonce:true,
            rowNum: 10000,
            multiselect:true,
            pager : this.pageGridId,
            ondblClickRow: function (rowId, iRow, iCol, e) {
                /*if(rowId=='TOTAL_BANCO') {
                    $("#pageTab_grid").hide();
                    $("#pageTab_detalleIngresoParcial").show();
                    GridIngresoIP.reloadGrid({'mes':(iCol-1),'year':DateControls.yearSelected});
                }else{
                    ingresoId=$("#grid-table tr#"+rowId +" td").eq(iCol).children('span').attr('cell-id');
                    
                    if(ingresoId){
                        $("#trigger").click();
                        $(".wrapperPagoMensual").hide();
                        $("#btnViewPagoMensual").show();
                        $("#btnOcultarPagoMensual").hide();
                        $("#btnSavePagoMensual").hide();
                        LoadDataIngreso.general(ingresoId);
                    }
                }*/
                
            },
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
                url: this.usuarioController, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
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

GridUsuario.init();




var Action= {
    saveAction: 'seguridad/useraccess/save',
    readAction: 'seguridad/useraccess/read',
    ruta:$(".breadcrumb").text().trim(),
    currentAmbiente:'E',
    readInfo:function(){
        
        var grupoName=null;
        var grupoRows=null;
        var menuId=null;
        var menuName=null;

        var htmlMenu='';
    
        $.post(this.readAction,{
            about:'getMenuPorAmbiente',
            tipoAmbiente: this.currentAmbiente
        },function(data){
            $.each(data,function(index){
                grupoName=data[index]['grupo'];
                grupoId=data[index]['mgId'];
                grupoRows=data[index]['rows'];

                htmlMenu+='<div class="col-xs-12"><a href="javascript:void(0);" data-toggle="collapse" data-target="#ls_'+grupoId+'" class="icon-list fa fa-plus"></a>'
                +'<label><input type="checkbox" class="ace checkMG" classname-childs="mg_'+grupoId+'" />  <span class="lbl">'+grupoName+'</span></label>'
                +'<ul id="ls_'+grupoId+'" class="list-group collapse">';

                $.each(grupoRows,function(subIndex){
                    menuId=grupoRows[subIndex]['id'];
                    menuName=grupoRows[subIndex]['menu'];

                    htmlMenu+='<li class="list-group-item"><input type="checkbox" class="items ace mg_'+grupoId+'" value="'+menuId+'" /><span class="lbl">'+menuName+'</span></li>';

                });

                htmlMenu+='</div>';
            });

            $("#wrapper_opcionesSis").html(htmlMenu);


        },'json').fail(function(){
            alerta('Ingreso',"Error al intentar recuperar datos del servidor.",'error');
        });
    },
    inicialStateFormNewUser:function(){
        $("#form-1")[0].reset();
        $("#form-2")[0].reset();

        $("#form-1").removeClass('hidden');
        $("#form-2").addClass('hidden');

        $("#wrapper-logintop").removeClass('hidden');
        $("#wrapper-loginfooter").addClass('hidden');

        $("#wrapper-login").addClass('hidden');
        $("#wrapper-passwordDefault").removeClass('hidden');
        $("#wrapper-passwordPersonalizado").addClass('hidden');

    },
    AsignarPermisos: function(){
        $("#modalPermisos").modal('show');
        $(".modal-dialog").css({'width': '50%', 'right': '0%' });
        Action.readInfo();


    },

    save:function(){

        stringItemsId='';
        $("#wrapper_opcionesSis input.items").each(function(){
            if($(this).is(':checked') ){
                stringItemsId+=","+ $(this).val();
            }
        });

        var usuariosSeleccionados = $(GridUsuario.gridId).jqGrid('getGridParam','selarrrow');

        $.post(this.saveAction,{
                about:'savePermiso',
                tipoAmbiente:this.currentAmbiente,
                usuarios:usuariosSeleccionados,
                items:stringItemsId
            },function(data){
                if(data.tipo && data.mensaje){
                    alerta('Acceso de usuarios',data.mensaje,data.tipo);
                    $("#modalPermisos").modal('hide');
                }else{
                    alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar actualizar el registro','error');
                }

        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar actualizar el registro','error');
        });


    },
    recordarDatos: function(){

        var usuariosSeleccionados = $(GridUsuario.gridId).jqGrid('getGridParam','selarrrow');

        $.post(this.saveAction,{
                about:'recordarDatos',
                usuarios:usuariosSeleccionados,
            },function(data){
                if(data.tipo && data.mensaje){
                    alerta('Acceso de usuarios',data.mensaje,data.tipo);
                }else{
                    alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar enviar datos de acceso por correo','error');
                }

        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar enviar datos de acceso por correo','error');
        });
    }
}




$("#wrapper_opcionesSis").on('click','.checkMG',function(){
    var classCheckParent=$(this).attr('classname-childs');

    if($(this).is(':checked') ){
        $("input."+classCheckParent).each(function(){
            $(this).prop('checked', 'true');
        });
    }else{
        $("input."+classCheckParent).each(function(){
            $(this).removeAttr('checked');
        });
    }   
});


//show o hidden a botones de la barra.
$(".page-content").on('click','input[type="checkbox"]',function(){
    var rowsSelected1 = $(GridUsuario.gridId).jqGrid('getGridParam','selarrrow');
   
    if(rowsSelected1.length>0){
        $("#btn-actions").removeClass('hidden'); 
    }else{
        $("#btn-actions").addClass('hidden');
    }
});



//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridUsuario.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridUsuario.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-270);
})

//resize on sidebar collapse/expand
var parent_column = $(GridUsuario.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout, solo para dar tiempo a los cambios del DOM y luego volver a dibujar el grid.
        setTimeout(function(){
            $(GridUsuario.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});
$(window).triggerHandler('resize.jqGrid');
