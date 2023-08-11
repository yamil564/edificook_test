var GridIngreso={
    ingresosController:"ue/ingreso/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",
    init:function(){
        $(this.gridId).jqGrid({
            url: this.ingresosController,
            datatype: "json",
            mtype: "POST",
            colNames:['',"Descripción","Ene",'Feb',"Mar","Abr","May",'Jun','Jul','Ago','Sep','Oct','Nov','Dic','T. Anual'],
            colModel:[
                    {name:'id',index:'id', width:50,frozen:true,'formatter':GridIngreso.formatChk,search:false},
                    {name:'descripcion',index:'descripcion', width:200,frozen:true,search:false,classes:'descripcion-unidad'},
                    {name:'ene',index:'ene', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'feb',index:'feb', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'mar',index:'mar', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'abr',index:'abr', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'may',index:'may', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'jun',index:'jun', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'jul',index:'jul', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'ago',index:'ago', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'sep',index:'sep', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'oct',index:'oct', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'nov',index:'nov', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda,classes:'col-mes'},
                    {name:'dic',index:'dic', width:100,align:"right",sortable: false,search:false,'formatter':GridIngreso.formatearCelda},
                    {name:'total',index:'total', width:100,align:"right",sortable: false,search:false},
            ],
            shrinkToFit: false,
            rowList: [],
            pgbuttons: false,
            pgtext: null,
            viewrecords: false,
            height: 'auto',
            loadonce:true,
            rowNum: 10000,
            pager : this.pageGridId,
            ondblClickRow: function (rowId, iRow, iCol, e) {
                if(rowId=='TOTAL_BANCO'){
                    $("#pageTab_grid").hide();
                    $("#pageTab_detalleIngresoParcial").show();
                    GridIngresoIP.reloadGrid({'mes':(iCol-1),'year':DateControls.yearSelected});
                }else{
                    return null;
                }
                
            },
            loadComplete:function () {
                $("#TOTAL_COBRADO, table tr#TOTAL_PENDIENTE, table tr#TOTAL_BANCO").addClass('hidden');
                $("#TOTAL_INGRESO td:first").html('');
                buscar.unidad();
            }
        });

        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false});

        $("#gsh_grid-table_descripcion").html('<input type="text" id="gs_descripcion" class="ui-widget-content ui-corner-all" style="width:100%;" placeholder="Buscar...">');
    },
    reloadGrid:function(arrayParams){
        $(this.gridId).setGridParam(
            { 
                datatype: "json",
                url: this.ingresosController, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
    },
    formatChk:function(cellvalue,options,rowObject){
        if(cellvalue=='' || cellvalue == null){
            return "";
        }
        if(cellvalue=='TE'){
            return '<center onclick="Action.toggleTotalIngreso()" sytle="cursor:pointer;"><i id="btn-dtIngreso" class="ui-icon ace-icon fa fa-plus"></i></center>';
        }
        return "<center><label class='position-relative tooltipmsg'><input type='checkbox' id='chkCol"+cellvalue+ "' name='chkCol"+cellvalue+"' class='ace' onclick='GridIngreso.checkBoxSelected("+cellvalue+");'/><span class='lbl'></span></label></center>";
    },
    checkBoxSelected:function(id){
        inputSelectedId ='chkCol'+id;
        if($("#"+inputSelectedId).is(':checked')){
            $("#co_unidadId_ni").val(id);
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

GridIngreso.init();

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

var DateControls={
    yearSelected:$("#currentYear").html(),
    nextYear:function(){
        this.yearSelected++;
        $("#currentYear").html(this.yearSelected);
        GridIngreso.reloadGrid({year:this.yearSelected});
    },
    backYear:function(){
        this.yearSelected--;
        $("#currentYear").html(this.yearSelected);
        GridIngreso.reloadGrid({year:this.yearSelected});
    }
}

var Action= {
    toggleTotalIngreso:function(){
        $("#btn-dtIngreso").toggleClass('fa-minus');
        $("#TOTAL_COBRADO, table tr#TOTAL_PENDIENTE, table tr#TOTAL_BANCO").toggleClass('hidden');
    },
}



$("#viewUnidadesPrincipales").click(function(e){
    $("#subtitleIngreso").html('Unidades principales');
    GridIngreso.reloadGrid({year:this.yearSelected,'tipo_unidad':'principales'});
    e.preventDefault();
});
$("#viewUnidadesSecundarias").click(function(e){
    $("#subtitleIngreso").html('Unidades secundarias');
    GridIngreso.reloadGrid({year:this.yearSelected,'tipo_unidad':'secundarias'});
    e.preventDefault();
});


//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridIngreso.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridIngreso.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-270);
})
//resize on sidebar collapse/expand
var parent_column = $(GridIngreso.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout, solo para dar tiempo a los cambios del DOM y luego volver a dibujar el grid.
        setTimeout(function(){
            $(GridIngreso.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});
$(window).triggerHandler('resize.jqGrid');