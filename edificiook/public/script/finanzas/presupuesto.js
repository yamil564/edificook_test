var GridPresupuesto={

    presupuestoController:"finanzas/presupuesto/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",

    init:function(){
        
        $(this.gridId).jqGrid({
            url: this.presupuestoController,
            datatype: "json",
            mtype: "POST",
            colNames:["Descripción","Ene",'Feb',"Mar","Abr","May",'Jun','Jul','Ago','Sep','Oct','Nov','Dic','T. Anual'],
            colModel:[
                    {name:'descripcion',index:'descripcion', width:300},
                    {name:'ene',index:'ene', width:100,align:"right"},
                    {name:'feb',index:'feb', width:100,align:"right"},
                    {name:'mar',index:'mar', width:100,align:"right"},
                    {name:'abr',index:'abr', width:100,align:"right"},
                    {name:'may',index:'may', width:100,align:"right"},
                    {name:'jun',index:'jun', width:100,align:"right"},
                    {name:'jul',index:'jul', width:100,align:"right"},
                    {name:'ago',index:'ago', width:100,align:"right"},
                    {name:'sep',index:'sep', width:100,align:"right"},
                    {name:'oct',index:'oct', width:100,align:"right"},
                    {name:'nov',index:'nov', width:100,align:"right"},
                    {name:'dic',index:'dic', width:100,align:"right"},
                    {name:'total',index:'total', width:110,align:"right",},
            ],
            shrinkToFit: false,
            treeGrid:true,
            treeGridModel: 'adjacency',
            ExpandColumn: 'descripcion',
            height: 'auto',
            ExpandColClick: true,
            pager : this.pageGridId,
            viewrecords: true,
            loadComplete:function(){
                setTimeout(function(){
                    GridPresupuesto.updatePagerIcons(this.gridId)
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
                url: this.presupuestoController, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
    }
}

//operaciones de la barra de botones
var Action={
    saveAction:'finanzas/presupuesto/save',
    delAction:'finanzas/presupuesto/del',

    new:function(){
        $("#textTotal").val('');
        $("#modalFormPresupuesto").modal('show');
        $(".modal-dialog").css('width', '70%');
    },
    edit:function(){
        $("#modalFormPresupuesto").modal('show');
        $(".modal-dialog").css('width', '70%');
    },
    delete:function(){
        return false;
    },
    save:function(){
        var form=$("#form-presupuesto").serializeArray();
        $.post(this.saveAction,form,function(data){
            alerta('Presupuesto',data.mensaje,data.tipo);
            if(data.tipo=='informativo'){
                GridPresupuesto.reloadGrid({year:$("#currentYear").html()})
                $("#modalFormPresupuesto").modal("hide");
            }
        },'json')
        .fail(function(){
            alert('error');
        });
    },

    viewDetalle:function(conceptoId,proveedorId,mes){

        $("#modalEgresoEjecutado").modal('show');
        $(".modal-dialog").css('width', '70%');

        urlbase=$("base").attr('href');
        $("#dte_co_proveedorId").val(proveedorId);
        $("#dte_co_conceptoId").val(conceptoId);
        $("#dte_co_mes").val(mes);

        $.post('finanzas/egreso/read',{
            query:'egresosPorConceptoAndProveedor',
            conceptoId:conceptoId,
            proveedorId:proveedorId,
            mes:mes,
            year:DateControls.yearSelected
        },function(data){
            $("#textConceptoNombre").val('');
            $("#textProveedorRZ").val('');

            if(data.rows.length>0){
                $("#textConceptoNombre").val(data.concepto);
                $("#textProveedorRZ").val(data.proveedor);

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
                //$("#msg_listaPagos").addClass('hidden');
            }else{
                //$("#msg_listaPagos").removeClass('hidden');
            }

        },'json').fail(function(){
            alerta('Egreso','Ocurrió un error desconocido en el servidor al intentar recuperar el detalle de egresos','error');
        });
    },
    closeModal:function(){
        var validator = $("#form-presupuesto" ).validate();
        validator.resetForm();
        $("#form-presupuesto .form-group").removeClass('has-error');
    }
}

var DateControls={
    yearSelected:$("#currentYear").html(),
    nextYear:function(){
        this.yearSelected++;
        $("#currentYear").html(this.yearSelected);
        GridPresupuesto.reloadGrid({year:this.yearSelected});
    },
    backYear:function(){
        this.yearSelected--;
        $("#currentYear").html(this.yearSelected);
        GridPresupuesto.reloadGrid({year:this.yearSelected});
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


GridPresupuesto.init();

//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridPresupuesto.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridPresupuesto.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-300);
})

//resize on sidebar collapse/expand
var parent_column = $(GridPresupuesto.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
        setTimeout(function(){
            $(GridPresupuesto.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});
$(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size


$("#btnSave").click(function(){
    $("#submit").click();
});

var $Validate = $("#form-presupuesto").validate({
    rules : {
        selMes : { required : true },
        selYear : { required : true },
        selTipo : { required : true },
        selConcepto : { required : true },
        textTotal : { required : true },
    },
    messages : {
        selMes : { required : 'Seleccionar mes' },
        selYear : { required : 'Seleccionar año' },
        selTipo : { required : 'Seleccionar tipo' },
        selConcepto : { required : 'Seleccionar concepto' },
        textTotal : { required : 'Ingrese un total' },
    },
    highlight:function(e) {
        $(e).closest('.form-group').removeClass('has-info').addClass('has-error');
    },
    reset:function(form) {
        alert("ddd");
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
            error.insertAfter(element.parent());
        } 
    },
    submitHandler:function(){
        Action.save();
    },
    
});