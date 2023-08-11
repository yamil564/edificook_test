var GridMorosos={

    morososController:"ue/morosos/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",

    init:function(){
        
        $(this.gridId).jqGrid({
            url: this.morososController,
            datatype: "json",
            mtype: "POST",
            colNames:["Descripción",'Propietario',"Residente",'<center>Deuda<br> Vencida</center>',"<center>Antiguedad<br>(dias)</center>",'<center>Deuda Próxima <br>a Vencer</center>',"Deuda Total"],
            colModel:[
                {name:'descripcion',index:'descripcion', width:200},
                {name:'propietario',index:'propietario', width:250},
                {name:'residente',index:'residente', width:250},
                {name:'deudavencida',index:'deudavencida', width:100,align:"right",search:false},
                {name:'diastranscurridos',index:'diastranscurridos', width:100,align:"right",search:false},
                {name:'proximoavencer',index:'proximoavencer', width:100,align:"right",search:false},
                {name:'deudatotal',index:'deudatotal', width:100,align:"right",search:false},
            ],
            pager : this.pageGridId,

            rowList: [],
            pgbuttons: false,
            pgtext: null,
            viewrecords: true,
            height: 'auto',
            loadonce:true,
            rowNum: 10000,
            
            multipleSearch:true,
        });

        $(this.gridId).jqGrid('navGrid','#grid-pager',{edit:false,add:false,del:false});
        $(GridMorosos.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter: true});
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

GridMorosos.init();
//$(GridMorosos.gridId).jqGrid('navGrid',GridMorosos.pageGridId,{add:false,edit:false,del:false,refresh:true},{},{},{},{multipleSearch:true});
//$(GridMorosos.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter: true});



var DateControls={
    yearSelected:$("#currentYear").html(),
    nextYear:function(){
        this.yearSelected++;
        $("#currentYear").html(this.yearSelected);
        GridMorosos.reloadGrid({year:this.yearSelected});
    },
    backYear:function(){
        this.yearSelected--;
        $("#currentYear").html(this.yearSelected);
        GridMorosos.reloadGrid({year:this.yearSelected});
    }
}

Action={
    generarReporteMorosos:function(){

        if($("#btnConfirmarEliminarEP #label").html()=="Generando..."){
            alerta('Morosos','Espere por favor, esta en curso la generación de un reporte.',"error");
            return;
        }

        $("#btnConfirmarEliminarEP").toggleClass('active');
        $("#btnConfirmarEliminarEP #label").html('Generando...');

        var representaunidad=$('input[name="rd_representateUnidad"]:checked').val();

        var formData=$("#formMorosos").serializeArray();
         formData.push({name:'representaunidad',value:representaunidad});

        $.post("ue/morosos/descargar",formData,function(data){
            $("#btnConfirmarEliminarEP").toggleClass('active');
            $("#btnConfirmarEliminarEP #label").html('Generar');

            if(data.message == 'success'){
                var link = document.createElement("a");
                link.download = data.nombreFile;
                link.href = data.ruta;
                link.click();
            }else{
                if(data.mc!=''){
                    alerta('Error',data.mc,'error');
                }else{
                    alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
                }
            }
        },'json').fail(function(){
            $("#btnConfirmarEliminarEP").toggleClass('active');
            $("#btnConfirmarEliminarEP #label").html('Generar');
            
            alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
        });
    }
}


//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridMorosos.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridMorosos.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-300);
})
//resize on sidebar collapse/expand
var parent_column = $(GridMorosos.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout, solo para dar tiempo a los cambios del DOM y luego volver a dibujar el grid.
        setTimeout(function(){
            $(GridMorosos.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});
$(window).triggerHandler('resize.jqGrid');

$("#btnDescargar").click(function(){
    $("#modalExportarOptions").modal('show');
    $("#modalExportarOptions .modal-dialog").css("width","600px");
    

    $("#textMesDesde").val( $("#textMesActual").val());
    $("#textYearDesde").val( $("#textYearActual").val())

    $("#textMesHasta").val( $("#textMesActual").val() );
    $("#textYearHasta").val( $("#textYearActual").val() );

    $("#or_Propietario").prop('checked', true);
});