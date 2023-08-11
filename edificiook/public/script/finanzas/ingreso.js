var GridIngreso={
    ingresosController:"finanzas/ingreso/grid",
    readIngresoController:"finanzas/ingreso/read",
    parametrosFechaCuotaController:"proceso/cuota/parametrosfecha",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",
    mesUnidadSelected:null,
    idUnidadSelected:null,
    idUnidadAsociada:null,
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
            viewrecords: true,
            height: 'auto',
            loadonce:true,
            rowNum: 10000,
            pager : this.pageGridId,
            ondblClickRow: function (rowId, iRow, iCol, e) {

                $("#viewModalConceptos").removeClass("hidden");
                $("#idGrupoIngreso").attr("data-idingreso",""); //clear idregistro

                if(rowId=='TOTAL_BANCO'){
                    $("#pageTab_grid").hide();
                    $("#pageTab_detalleIngresoParcial").show();
                    GridIngresoIP.reloadGrid({'mes':(iCol-1),'year':DateControls.yearSelected});
                }else{

                    tipoUnidad=$("#grid-table tr#"+rowId +" td").eq(iCol).parent().find("td.descripcion-unidad").text();
                    nombreUnidad=tipoUnidad.split(" ")[0];
                    $(".unidadesAgrupadas").addClass("hidden");

                    ingresoId=$("#grid-table tr#"+rowId +" td").eq(iCol).children('span').attr('cell-id');
                    //console.log(ingresoId);

                    $("#idGrupoIngreso").attr("data-idingreso",ingresoId);
                    $("#totalMes").val('');
                    $('select').val(0).trigger("chosen:updated"); //reset select unidad
                    dataMonth=GridIngreso.getMonthColumn(iCol);
                    monthNumber=dataMonth.split("|")[0];
                    monthLetter=dataMonth.split("|")[1];
                    $("#modalGrupoUnidad .modal-title").text(tipoUnidad+" - "+monthLetter+" - "+DateControls.yearSelected);
                    GridIngreso.grupoUnidadFechaCuota();

                    GridIngreso.mesUnidadSelected=monthNumber;
                    GridIngreso.idUnidadSelected=rowId;

                    if(ingresoId==undefined){
                        if(nombreUnidad=='GRUPO'){
                            $("#btnAgruparUnidad").attr("disabled","");
                            $("#btn-actions-guc-concepto-nuevo, #btn-actions-guc-concepto-save, #formEditarGrupoUnidadConcepto, #panelListGrupoUnidadConcepto, #btn-actions-guc-concepto").addClass("hidden");
                            $("#panelListGrupoUnidad, .frmGrupoUnidad").removeClass("hidden");
                            $("#modalConfirmUnidadAsociado .modal-dialog").css({'right': '0'});
                            $("#modalDeleteUnidadAsociado .modal-dialog").css({'right': '0'});
                            $("#modalGrupoUnidad .modal-dialog").css({'width': '50%', 'right': '0'});
                            GridIngreso.listGrupoUnidades(GridIngreso.mesUnidadSelected,DateControls.yearSelected,GridIngreso.idUnidadSelected); //listar Grupo Unidades
                        }
                    }else{
                        $("#modalConfirmUnidadAsociado .modal-dialog").css({'right': '20%'});
                        $("#modalDeleteUnidadAsociado .modal-dialog").css({'right': '20%'});
                        $("#modalGrupoUnidad .modal-dialog").css({'width': '50%', 'right': '20%'});
                        ingresoId=$("#grid-table tr#"+rowId +" td").eq(iCol).children('span').attr('cell-id');
                        if(ingresoId){
                            $("#trigger").click();
                            $(".wrapperPagoMensual").hide();
                            $("#btnViewPagoMensual").show();
                            $("#btnOcultarPagoMensual").hide();
                            $("#btnSavePagoMensual").hide();
                            if(nombreUnidad=='GRUPO'){
                                $("#viewModalConceptos").addClass("hidden");
                                $(".unidadesAgrupadas").removeClass("hidden");
                            }
                            LoadDataIngreso.general(ingresoId);
                        }
                    }

                }
            },
            loadComplete:function () {
                $("#TOTAL_COBRADO, table tr#TOTAL_PENDIENTE, table tr#TOTAL_BANCO").addClass('hidden');
                buscar.unidad();
            }
        });

        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false});

        $("#gsh_grid-table_descripcion").html('<input type="text" id="gs_descripcion" class="ui-widget-content ui-corner-all" style="width:100%;" placeholder="Buscar...">');
    },
    getMonthColumn:function(column){
        switch (column) {
            case 2: return (column-1)+'|ENERO'; break;
            case 3: return (column-1)+'|FEBRERO'; break;
            case 4: return (column-1)+'|MARZO'; break;
            case 5: return (column-1)+'|ABRIL'; break;
            case 6: return (column-1)+'|MAYO'; break;
            case 7: return (column-1)+'|JUNIO'; break;
            case 8: return (column-1)+'|JULIO'; break;
            case 9: return (column-1)+'|AGOSTO'; break;
            case 10: return (column-1)+'|SEPTIEMBRE'; break;
            case 11: return (column-1)+'|OCTUBRE'; break;
            case 12: return (column-1)+'|NOVIEMBRE'; break;
            case 13: return (column-1)+'|DICIEMBRE'; break;
        }  
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
    },

    deleteUnidadAsociado:function(){
        var modalConfirmUnidadAsociado = $("#modalConfirmUnidadAsociado");
        modalConfirmUnidadAsociado.modal('show');
    },

    addGrupoUnidad:function()
    {
        var modalConfirmUnidadAsociado = $("#modalConfirmUnidadAsociado");
        $("#btnAgruparUnidad").attr("disabled","");
        idPadre=$("#btnAgruparUnidad").attr("data-id");
        month=$("#modalGrupoUnidad #inputMonth").attr("data-month");
        idUnidad=$("#selUnidad").val();
        totalMes=$("#totalMes").val();
        dEmision=$("#dEmision").attr("data-demision");
        dVence=$("#dVence").attr("data-dvence");
        idGrupoIngreso=$("#idGrupoIngreso").attr("data-idingreso");
        idGrupoIngreso=(idGrupoIngreso!=undefined)?idGrupoIngreso:'';
        if(idUnidad!='' && totalMes!=''){

            var infoData = [];
            infoData.push({name: 'about', value: 'addGrupoUnidad'});
            infoData.push({name: 'idUnidad', value: idUnidad});
            infoData.push({name: 'month', value: month});
            infoData.push({name: 'year', value: DateControls.yearSelected});
            infoData.push({name: 'idPadre', value: idPadre});
            infoData.push({name: 'totalMes', value: totalMes});
            infoData.push({name: 'idGrupoIng', value: idGrupoIngreso});
            infoData.push({name: 'dEmision', value: dEmision});
            infoData.push({name: 'dVence', value: dVence});
            $.post(GridIngreso.readIngresoController, infoData , function(data){
                if(data.response=='success'){
                    modalConfirmUnidadAsociado.modal('hide');
                    $("#totalMes").val('');
                    //$("#btnAgruparUnidad").removeAttr("disabled");
                    $('select').val(0).trigger("chosen:updated"); //reset select unidad
                    $("#idGrupoIngreso").attr("data-idingreso",data.idIngreso);
                    alerta('Ingreso', data.message, data.tipo);
                    LoadDataIngreso.general(data.idIngreso);
                    LoadDataIngreso.itemsConcepto();
                    GridIngreso.listGrupoUnidades(month,DateControls.yearSelected,idPadre);
                    GridIngreso.reloadGrid({year:DateControls.yearSelected});
                }else{
                    alerta('Ingreso', data.message, data.tipo);
                }
            },'json').fail(function(){
                alert('error');
            });

        }else{
            alerta('Ingreso','Por favor seleccione la unidad.','advertencia');
        }
    },

    listGrupoUnidades:function(month,year,idPadre)
    {
        $("#tblGrupoUnidad tbody").html('');

        $.post(GridIngreso.readIngresoController,{
            about:'allGrupoUnidad',
            month:month,
            year:year,
            idPadre:idPadre
        },function(data){
            contentTable='';
            optionUnidadPadre='';

            //list unidades
            optionUnidadPadre='<option  value="0" selected disabled>Seleccionar unidad</option>';
            $.each(data.listaUnidadesPadres,function(index,value){
                optionUnidadPadre+="<option value='"+value['idUnidad']+"'>"+value['descripcion']+"</option>";
            });

            if(data.rows.length>0){
                $.each(data.rows,function(index,value){
                    idUnidad=value['idUnidad'];
                    year=value['year'];
                    month=value['numberMonth'];
                    contentTable+="<tr><td valign='center'><button type='button' class='btn btn-danger btn-xs' onclick='actionGrupoUnidades.confirmarEliminarUnidadAsociado("+idUnidad+','+year+','+month+")' style='border-radius: 3px;'><i class='fa fa-trash'></i> Eliminar</button> <button data-iduni='"+value['idUnidad']+"' type='button' class='btn btn-info btn-xs' onclick='actionGrupoUnidades.viewDetalleConcepto("+value['idGrupoUnidad']+")' style='border-radius: 3px;'><i class='fa fa-eye'></i> Ver detalle</button></td><td>"+value['unidad']+"</td><td style='width:120px;'><input type='text' value='"+value['totalMonth']+"' disabled='' style='border: 0;width: 100%;text-align: center;'></td></tr>";
                });
                contentTable+="<tr><td></td><td><strong>TOTAL:</strong></td><td style='width:120px;'><span class='label label-success arrowed label-grupunidad'>S/. "+data.totalUnidades+"</span></td></tr>";
            }else{
                contentTable+="<tr><td style='text-align:center;' colspan='5'>NO HAY UNIDADES.</td></tr>";
            }

            $("#selUnidad").html(optionUnidadPadre);
            $("#selUnidad").trigger('chosen:updated');
            $("#tblGrupoUnidad tbody").html(contentTable);  
            $("#btnAgruparUnidad").attr("data-id",idPadre);
            $("#modalGrupoUnidad #inputMonth").attr("data-month",month);
            $("#modalGrupoUnidad").modal('show');
            $('[data-toggle="tooltip"]').tooltip();
            //get idunidad
            $("#tblGrupoUnidad tbody button").click(function(){ //new
                GridIngreso.idUnidadAsociada=$(this).attr("data-iduni");
            });


        },'json').fail(function(){
            console.log('error');
        });
    },

    grupoUnidadFechaCuota:function()
    {
        $.post(GridIngreso.parametrosFechaCuotaController,{},function(data){
            $("#dEmision").attr("data-demision",data.diaEmi);
            $("#dVence").attr("data-dvence",data.diaVence);
        },'json');
    }

}

$('#modalDeleteUnidadAsociado').on('show.bs.modal', function (event) {
    $("#modalDeleteUnidadAsociado").find(".btn-success").attr("data-id","");
});
$('#modalDeleteUnidadAsociado').on('hide.bs.modal', function (event) {
    $("#modalDeleteUnidadAsociado").find(".btn-success").attr("data-id","");
});

var actionGrupoUnidades = {

    modalDeleteUnidadAsociado:$("#modalDeleteUnidadAsociado"),
    
    confirmarEliminarUnidadAsociado: function(idUnidad,year,month){
        this.modalDeleteUnidadAsociado.modal("show");
        this.modalDeleteUnidadAsociado.find(".btn-success").attr("data-id",idUnidad+"|"+year+"|"+month);
    },

    eliminarUnidadAsociado: function(){
        var self = this;
        var data = this.modalDeleteUnidadAsociado.find(".btn-success").attr("data-id");
        if(data.length>0){
            var params = data.split("|");
            var idUnidad = params[0];
            var year = params[1];
            var month = params[2];
            if(idUnidad!="undefined" || year!="undefined" || month!="undefined"){
                if(idUnidad.length>0 && year.length>0 && month.length>0){
                    $.post("finanzas/ingreso/delete", {
                        about:'deleteUnidadAsociado',
                        idUnidad:idUnidad,
                        year:year,
                        month:month
                    }, function(data){
                        if(data.response=='success'){
                            self.modalDeleteUnidadAsociado.modal("hide");
                            LoadDataIngreso.general();
                            LoadDataIngreso.itemsConcepto();
                            GridIngreso.listGrupoUnidades(GridIngreso.mesUnidadSelected,DateControls.yearSelected,GridIngreso.idUnidadSelected);
                            GridIngreso.reloadGrid({year: DateControls.yearSelected});
                        }else{
                            self.modalDeleteUnidadAsociado.modal("hide");
                            alerta('Ingreso', 'Ocurrió un error desconocido en el servidor, vuelva a intentarlo por favor.', 'error');
                        }
                    },'json').fail(function(){
                        self.modalDeleteUnidadAsociado.modal("hide");
                        alerta('Ingreso', 'Ocurrió un error desconocido en el servidor, vuelva a intentarlo por favor.', 'error');
                    });
                }
            }
        }else{
            this.modalDeleteUnidadAsociado.modal("hide");
            alerta('Ingreso', 'Ocurrió un error desconocido en el servidor, vuelva a intentarlo por favor.', 'error');
        }

    },
    viewDetalleConcepto:function(idGrupoUnidad)
    {   
        $("#idGrupoUnidad_guc").val('');

        $.post(GridIngreso.readIngresoController,{
            about:'listGrupoUnidadConcepto',
            idGrupoUnidad:idGrupoUnidad
        },function(data){
            if(data.response=='success')
            {
                contentTable='';
                if(data.rows.length>0){

                    $(".frmGrupoUnidad").addClass("hidden");
                    $("#idGrupoUnidad_guc").val(idGrupoUnidad);

                    $.each(data.rows,function(index,value){
                        contentTable+="<tr><td valign='center'><label class='position-relative tooltipmsg'><input id='TC_chkCol"+value['idConcepto']+"' type='checkbox' class='ace' value='"+value['idConcepto']+"' data-iduni='"+value['idUnidad']+"' onclick='TableConceptosGrupoUnidad.checkBoxSelected("+value['idConcepto']+")'><span class='lbl'></span></label></td><td>"+value['descripcion']+"</td><td style='width:120px;'><input type='text' value='"+value['total']+"' disabled='' style='border: 0;width: 100%;text-align: center;'></td></tr>";
                    });

                    contentTable+="<tr><td></td><td><strong>Total Emisión:</strong></td><td style='width:120px;'><span class='label label-success arrowed label-grupunidad'>S/. "+data.totalEmision+"</span></td></tr>";
                    
                }else{
                    contentTable+="<tr><td style='text-align:center;' colspan='5'>NO HAY UNIDADES.</td></tr>";
                }

                $("#tblGrupoUnidadConcepto tbody").html(contentTable);
                //options
                $("#panelListGrupoUnidad").addClass("hidden");
                $("#panelListGrupoUnidadConcepto").removeClass("hidden");
                $("#btn-actions-guc-concepto-nuevo").removeClass("hidden");
            }
          
        },'json').fail(function(){
            console.log('error');
        });
    }
}

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



var GridIngresoIP={
    urlDataIP:"finanzas/ingreso/read",
    gridId:"#grid-tableIP",
    pageGridId:"#grid-pagerIP",

    init:function(){
        $(this.gridId).jqGrid({
            url: this.urlDataIP,
            datatype: "json",
            mtype: "POST",
            postData: {'about':'ingresosParcialesByMes'}, 
            colNames:['F.Pago',"Unidad","Importe",'N° Operación','Banco','Observación'],
            colModel:[
                    {name:'fechapago',index:'fechapago', width:40,frozen:true,search:true},
                    {name:'unidad',index:'unidad', width:200,search:true},
                    {name:'Importe',index:'Importe', width:80,frozen:true,align:"right",sortable: false,search:true},
                    {name:'operacion',index:'operacion', width:80,frozen:true,align:"right",sortable: false,search:true},
                    {name:'banco',index:'banco',align:"left",sortable: false,search:false},
                    {name:'obs',index:'obs',align:"left",sortable: false,search:false},

            ],
            width:'100%',
            shrinkToFit: true,
            rowList: [],
            pgbuttons: false,
            pgtext: null,
            footerrow : true,
            userDataOnFooter : true,
            viewrecords: true,
            height: 500,
            loadonce:true,
            rowNum: 10000,
            pager : this.pageGridId,
            multiselect: false,
        });

        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false});
    },
    reloadGrid:function(arrayParams){
        $(this.gridId).setGridParam(
            { 
                datatype: "json",
                url: this.urlDataIP, 
                postData: arrayParams, 
            }
        );
        $(this.gridId).trigger("reloadGrid");
    },
}

GridIngresoIP.init();

var Action= {
    saveAction: 'finanzas/ingreso/save',
    readAction: 'finanzas/ingreso/read',
    ruta:$(".breadcrumb").text().trim(),

    toggleTotalIngreso:function(){
        $("#btn-dtIngreso").toggleClass('fa-minus');
        $("#TOTAL_COBRADO, table tr#TOTAL_PENDIENTE, table tr#TOTAL_BANCO").toggleClass('hidden');
    },
    viewFormPagoMensual: function () {
        $(".wrapperPagoMensual").show('fast');
        $("#textImporte").focus();
        $("#btnViewPagoMensual").hide();
        $("#panelGroup_dtEmision").hide();
        $("#btnOcultarPagoMensual").show();
        $("#btnSavePagoMensual").show();
    },
    backPagoMensual: function () {
        $(".wrapperPagoMensual").hide('fast');
        $("#btnViewPagoMensual").show('fast');
        $("#panelGroup_dtEmision").show();
        $("#btnOcultarPagoMensual").hide();
        $("#btnSavePagoMensual").hide();
    },
    registrarPagoMensual: function () {
        formData = $("#form-pagomensual").serializeArray();
        ingresoId = $("#co_ingresoId").val();
        formData.push({name: 'ingresoId', value: ingresoId});
        formData.push({name: 'about', value: 'pagoMensual'});
        $.post(this.saveAction, formData, function (data) {
            if (data.tipo) {
                alerta('Ingreso', data.mensaje, data.tipo);
                if (data.tipo == 'informativo') {
                    GridIngreso.reloadGrid({year: DateControls.yearSelected});
                    LoadDataIngreso.general(ingresoId);
                    $("#form-pagomensual")[0].reset();
                    Action.backPagoMensual();
                }
            } else {
                alerta('Ingreso', 'Ocurrió un error desconocido en el servidor al intentar registrar un pago', 'error');
            }
        }, 'json').fail(function () {
            alerta('Ingreso', 'Ocurrió un error desconocido en el servidor al intentar registrar un pago', 'error');
        });
    },
    viewModalPagoTotal: function () {
        $("#modalPagoTotal").modal('show');
        $(".modal-dialog").css({'width': '50%', 'right': '20%'});
        LoadDataIngreso.itemsPendientesDePago();
    },
    registrarPagoTotal: function () {
        formData = $("#form-pagototal").serializeArray();
        unidadId = $("#co_unidadId").val();
        formData.push({name: 'unidadId', value: unidadId});
        formData.push({name: 'about', value: 'pagoTotal'});
        $.post(this.saveAction, formData, function (data) {
            if (data.tipo) {
                alerta('Ingreso', data.mensaje, data.tipo);
                if (data.tipo == 'informativo') {
                    $("#form-pagototal")[0].reset();
                    GridIngreso.reloadGrid({year: DateControls.yearSelected});
                    LoadDataIngreso.general(ingresoId);
                    $("#modalPagoTotal").modal('hide');
                }
            } else {
                alerta('Ingreso', 'Ocurrió un error desconocido en el servidor al intentar registrar un pago', 'error');
            }
            $("#form-presupuesto .form-group").removeClass('has-error');
        }, 'json').fail(function () {
            alerta('Ingreso', 'Ocurrió un error desconocido en el servidor al intentar registrar un pago', 'error');
        });
    },
    viewModalListarPagos: function () {
        $("#modalListarPagos").modal('show');
        $(".modal-dialog").css({'width': '60%', 'right': '18%'});

        $("#form-editarPago input:text,#form-editarPago select").attr('disabled', 'true');
        $("#form-editarPago")[0].reset();

        if (!$("#btn-actions-pagos-save").hasClass('hidden')) {
            $("#btn-actions-pagos-save").addClass('hidden');
        }
        if (!$("#btn-actions-pagos").hasClass('hidden')) {
            $("#btn-actions-pagos").addClass('hidden');
        }
        if (!$("#btn-actions-pagos-delete").hasClass('hidden')) {
            $("#btn-actions-pagos-delete").addClass('hidden');
        }
        LoadDataIngreso.itemsPagosRegistrados();
    },

    viewModalUnidadesAgrupadas: function(){
        $("#modalGrupoUnidad .modal-dialog").css({'width': '50%', 'right': '20%'});
        GridIngreso.listGrupoUnidades(GridIngreso.mesUnidadSelected,DateControls.yearSelected,GridIngreso.idUnidadSelected);
    },

    viewModalConceptos: function () {
        $("#modalConceptos").modal('show');
        $(".modal-dialog").css({'width': '50%', 'right': '20%'});

        $("#formEditarConcepto input:text").attr('disabled', 'true');

        $("#formEditarConcepto , #formEditarConcepto").addClass('hidden');
        $("#formNuevoConcepto,#formEditarConcepto")[0].reset();

        $("#btn-actions-concepto-save").addClass('hidden');
        $("#btn-actions-concepto").addClass('hidden');
        $("#btn-actions-concepto-delete").addClass('hidden');

        if(!$("#formNuevoConcepto").hasClass('hidden')){
            $("#formNuevoConcepto").addClass('hidden');
        }

        if(!$("#btn-actions-concepto-add").hasClass('hidden')){
            $("#btn-actions-concepto-add").addClass('hidden');
        }

        if($("#btn-actions-concepto-nuevo").hasClass('hidden')){
            $("#btn-actions-concepto-nuevo").removeClass('hidden');
        }

        LoadDataIngreso.itemsConcepto();
        LoadDataIngreso.listConceptos();
    },
    viewModalConceptosReadOnly: function () {
        $("#modalConceptosReadOnly").modal('show');
        $(".modal-dialog").css({'width': '50%', 'right': '20%'});
        LoadDataIngreso.itemsConcepto();
    },
    viewModalIngreso: function () {
        $("#modalNuevoIngreso").modal('show');
        $(".modal-dialog").css({'width': '60%', 'right': '0%'});

        var validator = $("#formNuevoIngreso").validate();
        validator.resetForm();
        $("#formNuevoIngreso .form-group").removeClass('has-error');

        LoadDataIngreso.detalleUnidad();
        LoadDataIngreso.listConceptos();
        Action.escribirParametrosfechaEmision();
        $("#textFechaEmi").prop('disabled',true);
        $("#textFechaVence").prop('disabled',true);
        $("#chkPersonalizarFechaEmision").prop('checked',false);
    },
    registrarIngreso: function(){
        var fechaPerzonalizada=false;

        if($("#chkPersonalizarFechaEmision").is(':checked')){
            fechaPerzonalizada=true;
        }
        formData = $("#formNuevoIngreso").serializeArray();
        unidadId = $("#co_unidadId_ni").val();
        formData.push({name: 'unidadId', value: unidadId});
        formData.push({name:'fechaPerzonalizada',value:fechaPerzonalizada});
        formData.push({name: 'about', value: 'registrarIngreso'});
        formData.push({name: 'ruta', value: this.ruta});
        $.post(this.saveAction, formData, function (data) {
            if (data.tipo){
                alerta('Ingreso', data.mensaje, data.tipo);
                if (data.tipo == 'informativo') {
                    $("#formNuevoIngreso")[0].reset();
                    GridIngreso.reloadGrid({year: DateControls.yearSelected});
                    $("#modalNuevoIngreso").modal('hide');
                }
            } else {
                alerta('Ingreso', 'Ocurrió un error desconocido en el servidor al intentar registrar el ingreso', 'error');
            }
            $("#formNuevoIngreso .form-group").removeClass('has-error');
        }, 'json').fail(function () {
            alerta('Ingreso', 'Ocurrió un error desconocido en el servidor al intentar registrar el ingreso', 'error');
        });
    },
    personalizarFechaEmision:function(){
        Action.escribirParametrosfechaEmision();
        if($("#chkPersonalizarFechaEmision").is(':checked')){
            $("#textFechaEmi").prop('disabled',false);
            $("#textFechaVence").prop('disabled',false);
        }else{
            $("#textFechaEmi").prop('disabled',true);
            $("#textFechaVence").prop('disabled',true);
        }
    },
    escribirParametrosfechaEmision:function(){
        params=null;
        $.post('proceso/cuota/parametrosfecha',params,function(data){
            $("#textFechaEmi").val(data.fechaEmi);
            $("#textFechaVence").val(data.fechaVence);
            $("#textFechaEmi_1").val(data.fechaEmi);
            $("#textFechaVence_1").val(data.fechaVence);
        },'json');
    },
    generateExcelBcp:function(){
        if($("#btnGenerarReporteBCP #label").html()=="Generando..."){
            alerta('Morosos','Espere por favor, esta en curso la generación de un reporte.',"error");
            return;
        }
        $("#btnGenerarReporteBCP").toggleClass('active');
        $("#btnGenerarReporteBCP #label").html('Generando...');

        var representaunidad=$('input[name="rd_representateUnidadBcp"]:checked').val();
        var formData=new Array();
            formData.push({name:'representaunidad',value:representaunidad});
            formData.push({name:'mes',value:$("#textMesEmisionBcp").val()});
            formData.push({name:'year',value:$("#textYearEmisionBcp").val()});
            formData.push({name:'route',value:'generateExcelBcp'});
        $.post("finanzas/ingreso/descargar",formData,function(data){
            
            $("#btnGenerarReporteBCP").toggleClass('active');
            $("#btnGenerarReporteBCP #label").html('Generar');

            if(data.response=='success'){
                var link = document.createElement("a");
                link.download = data.nombreFile;
                link.href = data.ruta;
                link.click();
            }else if(data.response=='nounidad'){

            }else{
                alerta("Ingreso", data.message, data.tipo);
                $("#btnGenerarReporteBCP").toggleClass('active');
                $("#btnGenerarReporteBCP #label").html('Generar');
            }
        },'json').fail(function(){
            alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
        });
    },
    descargar:function(){
        
        if($("#btnGenerarReporte #label").html()=="Generando..."){
            alerta('Morosos','Espere por favor, esta en curso la generación de un reporte.',"error");
            return;
        }

        $("#btnGenerarReporte").toggleClass('active');
        $("#btnGenerarReporte #label").html('Generando...');

        var tiporeporte=$('input[name="rd_tiporeporte"]:checked').val();
        
        var idsConceptosSelected=''
        $('input[name="checkItemConceptoIngreso"]:checked').each(function(){
            idsConceptosSelected+=","+$(this).val();
        });
        var representaunidad=$('input[name="rd_representateUnidad"]:checked').val();

        var formData=new Array();
            formData.push({name:'tiporeporte',value:tiporeporte});
            formData.push({name:'representaunidad',value:representaunidad});
            formData.push({name:'ids',value:idsConceptosSelected});
            formData.push({name:'mes',value:$("#textMesEmision").val()});
            formData.push({name:'year',value:$("#textYearEmision").val()});
            formData.push({name:'ruta',value:this.ruta});

        $.post("finanzas/ingreso/descargar",formData,function(data){
            $("#btnGenerarReporte").toggleClass('active');
            $("#btnGenerarReporte #label").html('Generar');

            if(data.message == 'success'){
                var link = document.createElement("a");
                link.download = data.nombreFile;
                link.href = data.ruta;
                link.click();
            }else{
                alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
                $("#btnGenerarReporte").toggleClass('active');
                $("#btnGenerarReporte #label").html('Generar');
            }
        },'json').fail(function(){
            alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
        });
    
    },
    descargarFormatoConcepto:function(){
        $.post("finanzas/ingreso/formatoconcepto",{
            accion:'descargar',
        },function(data){
            if(data.message == 'success'){
                alerta('Consumo','Formato generado correctamente.','informativo');
                var url = data.ruta;
                var name = data.nombreFile;
                //consumo.formatoNombreTemporal = data.nombreFile;
                //consumo.downloadURI(url,name);

                var link = document.createElement("a");
                link.download = name;
                link.href = url;
                link.click();


            }else{
                alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
            }
        },'json').fail(function(){
            alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
        });
    },
    importarConceptoDesdeExcel:function(){
           if(Action.fileConceptoName==""){
               alerta("Error","Adjuntar formato");
               return;
           }
           swal({
                  title: '',
                  text: "Estás seguro que desea importar este archivo?",
                  type: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#3085d6',
                  cancelButtonColor: '#d33',
                  cancelButtonText:'Cancelar',
                  confirmButtonText: 'Aceptar',
            }).then(function () {

                $.post(Action.saveAction,{
                    filename:Action.fileConceptoName,
                    about:'registrarConceptosExcel'
                },function(data){

                    if(data.tipo){
                        if(data.tipo=='error'){
                            alerta("Ingreso", data.mensaje, 'error');
                            return;
                        }

                        if(data.tipo == 'advertencia'){
                            alerta('Ingreso',data.mensaje,'advertencia');
                            return;
                        }

                        alerta('Ingreso',data.mensaje,'informativo');
                    }


                    $("#modalImportar").modal('hide');
                    GridIngreso.reloadGrid({year:DateControls.yearSelected});

                },'json').fail(function(){
                    alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
                });
            });
    },
    tooltip:function(){
        $(".tooltipmsg").tooltip({
            show:{delay:100},
            hide: {
                effect: "fast",
                delay: 150
            },
            position: {
             my: "left bottom-5",
             at: "left top",
            },
            open: function( event, ui ) {
                            //ui.tooltip.animate({ top: ui.tooltip.position().top + 2 }, "fast");
                }
        });
    }
}
GridIngreso.init();

var LoadDataIngreso={
    readAction:'finanzas/ingreso/read',
    detalleUnidad:function(){
        unidadId=$("#co_unidadId_ni").val();
        $.post(this.readAction,{
            about:'detalleUnidad',
            unidadId:unidadId
        },function(data){
            $("#spnUnidad_ni").html(data.unidadNombre);
            $("#spnPropietario_ni").html(data.propietario);
            $("#spnResidente_ni").html(data.residente);
            $("#spnDeudaTotalUnidad_ni").html(data.deudaTotalUnidad);
        },'json').fail(function(){
            alerta('Ingreso',"Error al intentar recuperar datos del servidor.",'error');
        });
    },
    general:function(ingresoId){
        $("#form-pagomensual")[0].reset();
        
        if(ingresoId==undefined){
           ingresoId=$("#co_ingresoId").val(); 
        }

        $.post(this.readAction,{
            about:'general',
            ingresoId:ingresoId
        },function(data){
            $("#co_ingresoId").val(ingresoId);
            $("#co_unidadId").val(data.unidadId);
            $("#spnPeriodo").html(data.mes);
            $("#spnUnidad").html(data.unidadNombre);
            $("#spnPropietario").html(data.propietario);
            $("#spnResidente").html(data.residente);
            $("#spnDeudaTotalUnidad").html(data.deudaTotalUnidad);
            $("#spnDocumento").html(data.serie+'-'+data.nroDoc);
            $("#spnFechaEmi").html(data.fechaEmi);
            $("#spnFechaVence").html(data.fechaVence);
            $("#spnTotalEmision").html(data.totalMesEmision);
            $("#spnTotalPagado").html(data.totalMesPagado);
            $("#spnTotalDebe").html(data.debeMes);
            var debeMes=data.debeMes;
            $("#textImporte_PM").val(debeMes.replace(',',''));
        },'json').fail(function(){
            alerta('Ingreso',"Error al intentar recuperar datos del servidor.",'error');
        });
    },
    itemsConcepto:function(ingresoId){
        if(ingresoId==undefined){
           ingresoId=$("#co_ingresoId").val(); 
        }
        $.post(this.readAction,{
            about:'itemsConcepto',
            ingresoId:ingresoId
        },function(data){
            contentTable='';
            $.each(data.rows,function(index,value){
    
                nota='<td></td>';
                if(value['nota']!=''){
                    nota='<td><span class="tooltipmsg ace-icon fa fa-comment bigger-130"  title="'+value['nota']+'"></span></td>';
                }
                contentTable+="<tr>"
                    +"<td>"
                        +"<label class='position-relative tooltipmsg'>"
                            +"<input id='TC_chkCol"+value['conceptoId']+"' type='checkbox' class='ace' value='"+value['conceptoId']+"' onclick='TableConceptos.checkBoxSelected("+value['conceptoId']+")'>"
                            +"<span class='lbl'></span>"
                        +"</label>"
                    +"</td>"
                    +"<td>"+value['concepto']+"</td>"
                    +"<td align='right'>"+value['total']+"</td>"+nota
                +"</tr>";
            });
            totalesTable="<tr>"
                +"<th></th>"
                +"<th>"+data.total['label']+"</th>"
                +"<th><div style='text-align: right;'><span class='label label-danger arrowed'>S/. "+data.total['SumTotal']+"</span></div></th>"
                +"<th></th>"
            +"</tr>";

            $("#tblConceptosReadOnly tbody, #tblConceptos tbody").html(contentTable);
            $("#tblConceptosReadOnly tfoot, #tblConceptos tfoot").html(totalesTable);
            Action.tooltip();

        },'json').fail(function(){
            alerta('Ingreso',"Error al intentar recuperar datos del servidor.",'error');
        });
    },
    itemsPendientesDePago:function(unidadId){
        if(unidadId==undefined){
            unidadId=$("#co_unidadId").val();
        }
        $.post(this.readAction,{
            about:'itemsPendientesDePago',
            unidadId:unidadId
        },function(data){
            contentTable='';
            $.each(data.rows,function(index,value){
                contentTable+="<tr>"
                    +"<td>"+value['mes']+"</td>"
                    +"<td align='right'>"+value['totalEmision']+"</td>"
                    +"<td align='right'>"+value['totalPagado']+"</td>"
                    +"<td align='right'>"+value['debe']+"</td>"
                +"</tr>";
            });
            totalesTable="<tr>"
                +"<th>"+data.total['label']+"</th>"
                +"<th><div style='text-align:right;'>"+data.total['sumEmision']+"</div></th>"
                +"<th><div style='text-align:right;'>"+data.total['sumPagado']+"</div></th>"
                +"<th><div style='text-align:right;'><span class='label label-danger arrowed'>S/. "+data.total['sumDebe']+"</span></div></th>"
            +"</tr>";
            $("#tblIngresosPendienteDePago tbody").html(contentTable);
            $("#tblIngresosPendienteDePago tfoot").html(totalesTable);
            var totalDebe=data.total['sumDebe'];
            $("#textImporte_PT").val(totalDebe.replace(',',''));
        },'json').fail(function(){
            alerta('Ingreso',"Error al intentar recuperar datos del servidor.",'error');
        });
    },
    itemsPagosRegistrados:function(ingresoId){
        if(ingresoId==undefined){
            ingresoId=$("#co_ingresoId").val();
        }

        $.post(this.readAction,{
            about:'itemsPagosRegistrados',
            ingresoId:ingresoId
        },function(data){
            contentTable='';
            $.each(data.rows,function(index,value){
                var obs='';
                if(value['obs']!=''){
                    var obs="<span class='tooltipmsg ace-icon fa fa-comment  bigger-130' title='"+value['obs']+"'></span>";
                }
                

                contentTable+="<tr>"
                +"<td>"
                    +"<label class='position-relative tooltipmsg'>"
                        +"<input id='chkCol"+value['id']+"' type='checkbox' class='ace' value='"+value['id']+"' onclick='TablePagos.checkBoxSelected("+value['id']+")'>"
                        +"<span class='lbl'></span>"
                    +"</label>"
                +"</td>"
                +"<td>"+value['fechaPago']+"</td>"
                +"<td>"+value['tipoDoc']+"</td>"
                +"<td>"+value['nroOperacion']+"</td>"
                +"<td>"+value['banco']+"</td>"
                +"<td align='right'>"+value['importe']+"</td>"
                +"<td align='right'>"+obs+"</td>"
                +"</tr>";
            });

            totalesTable="<tr>"
                +"<th></th>"
                +"<th></th>"
                +"<th></th>"
                +"<th></th>"
                +"<th>"+data.total['label']+"</th>"
                +"<th><div style='text-align:right;'><span class='label label-success arrowed'>S/. "+data.total['sumImporte']+"</span></div></th>"
            +"</tr>";

            $("#tblIngresosParciales tbody").html(contentTable);
            $("#tblIngresosParciales tfoot").html(totalesTable);
            Action.tooltip();

        },'json').fail(function(){
            alerta('Ingreso',"Error al intentar recuperar datos del servidor.",'error');
        });
    },
    listConceptos:function(){
        formdata=new Array();
        formdata.push({name:'about',value:'allConceptos'});
        $.post(this.readAction,formdata,function(data){
            options='<option  value="0" selected disabled>Seleccionar concepto</option>';
            $.each(data,function(index,value){
                options+='<option value="'+value['id']+'">'+value['descripcion']+'</option>';
            });
            $("#selConcepto_nc").html(options);
            $("#selConcepto_nc").trigger('chosen:updated');

            $("#selConcepto_ni").html(options);
            $("#selConcepto_ni").trigger('chosen:updated');
        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar listar los conceptos','error');
        });
    }
}

TablePagos={
    idTable:'#tblIngresosParciales',
    saveAction:'finanzas/ingreso/save',
    deleteAction:'finanzas/ingreso/delete',
    checkBoxSelected:function(id){
        inputSelectedId = 'chkCol'+id;
        if($("#"+inputSelectedId).is(':checked')){
            $(this.idTable+" input[type='checkbox']").each(function(index,value){
                idCurrentInput=$(value).attr('id');
                if(idCurrentInput != inputSelectedId){
                    $("#"+idCurrentInput).attr('checked', false);
                } else{
                    $("#btn-actions-pagos").removeClass('hidden');
                    $("#btn-actions-pagos-save").addClass('hidden');
                    $("#btn-actions-pagos-delete").addClass('hidden');
                    $("#form-editarPago input:text,#form-editarPago select").attr('disabled','true');
                }
            });
            TablePagos.getCurrentRow(id);
        }else{
            $("#btn-actions-pagos").addClass('hidden');
            $("#co_pagoId").val(0);
            $("#form-editarPago")[0].reset();
        }
    },
    getCurrentRow:function(id){
        tablerow=$("#chkCol"+id).parent().parent().parent();
        fecha=tablerow.children('td:eq(1)').html();
        arrayfecha=fecha.split("-");
        fecha=arrayfecha[2]+"-"+arrayfecha[1]+"-"+arrayfecha[0];

        tipodoc=tablerow.children('td:eq(2)').html();
        nroOperacion=tablerow.children('td:eq(3)').html();
        banco=tablerow.children('td:eq(4)').html();
        // setdata
        $("#co_pagoId").val(id);
        $("#selBanco_EP").val(banco);
        $("#textNroOperacion_EP").val(nroOperacion);
        $("#textFechaPago_EP").val(fecha);
        $("#selTipoDoc_EP").val(tipodoc);
    },
    activateEditForm:function(){
        $("#btn-actions-pagos").addClass('hidden');
        $("#btn-actions-pagos-save").removeClass('hidden');
        $("#form-editarPago input:text,#form-editarPago select").removeAttr('disabled');
    },
    cancelEditForm:function(){
        $("#btn-actions-pagos").removeClass('hidden');
        $("#btn-actions-pagos-save").addClass('hidden');
        $("#form-editarPago input:text, #form-editarPago select").attr('disabled','true');
    },
    save:function(){
        formData=$("#form-editarPago").serializeArray();
        formData.push({name:'id',value:$("#co_pagoId").val()});
        formData.push({name:'about',value:'updateIngresoParcial'});
        $.post(this.saveAction,formData,function(data){
            if(data.tipo && data.mensaje){
                alerta('Lista de Pagos',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    LoadDataIngreso.itemsPagosRegistrados();
                    $("#form-editarPago")[0].reset();
                    $("#btn-actions-pagos").addClass('hidden');
                    $("#btn-actions-pagos-save").addClass('hidden');
                }
            }else{
                alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar actualizar el registro','error');
            }
        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar actualizar el registro','error');
        });
    },
    activateDelForm:function(){
        $("#btn-actions-pagos").addClass('hidden');
        $("#btn-actions-pagos-delete").removeClass('hidden');
    },
    cancelDelForm:function(){
        $("#btn-actions-pagos").removeClass('hidden');
        $("#btn-actions-pagos-delete").addClass('hidden');
    },
    deletePago:function(){
        formData=new Array();
        formData.push({name:'id',value:$("#co_pagoId").val()});
        formData.push({name:'unidadId',value:$("#co_unidadId").val()});
        formData.push({name:'about',value:'deleteIngresoParcial'});
        $.post(this.deleteAction,formData,function(data){
            if(data.tipo && data.mensaje){
                alerta('Lista de Pagos',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    LoadDataIngreso.itemsPagosRegistrados();
                    LoadDataIngreso.general();
                    GridIngreso.reloadGrid({year:this.yearSelected});
                    $("#form-editarPago")[0].reset();
                    $("#btn-actions-pagos-delete").addClass('hidden');
                }
            }else{
                alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar eliminar el registro','error');
            }
        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar eliminar el registro','error');
        });
    }
}

var TableConceptosGrupoUnidad={
    idTable:'#tblGrupoUnidadConcepto',
    saveAction:'finanzas/ingreso/save',

    checkBoxSelected:function(id)
    {
        inputSelectedId = 'TC_chkCol'+id;

        if($("#tblGrupoUnidadConcepto #"+inputSelectedId).is(':checked')){

            $("#btn-actions-guc-concepto, #btn-actions-guc-concepto-save").removeClass("hidden");

            $("#tblGrupoUnidadConcepto input[type='checkbox']").each(function(index,value){
                idCurrentInput=$(value).attr('id');
                idUnidad=$("#"+idCurrentInput).attr("data-iduni");
                if(idCurrentInput != inputSelectedId){
                    $("#tblGrupoUnidadConcepto #"+idCurrentInput).attr('checked', false);
                } else{
                    $("#btn-actions-guc-concepto-nuevo, #btn-actions-guc-concepto-save, .frmGrupoUnidad").addClass("hidden");
                    $("#btn-actions-guc-concepto").removeClass('hidden');
                    $("#formEditarGrupoUnidadConcepto input:text").attr('disabled','true');
                }
            });
            TableConceptosGrupoUnidad.getCurrentRow(id);
        }else{
            $("#btn-actions-guc-concepto-nuevo").removeClass('hidden');
            $("#btn-actions-guc-concepto, #btn-actions-guc-concepto-save").addClass('hidden');
            $("#co_conceptoId_guc").val(0);
            $("#formEditarGrupoUnidadConcepto").addClass('hidden')[0].reset();
        }
    },
    getCurrentRow:function(id)
    {
        tablerow=$("#tblGrupoUnidadConcepto #TC_chkCol"+id).parent().parent().parent();
        totalConcepto=tablerow.find("td:eq(2) input").val();
        $("#co_conceptoId_guc").val(id);
        $("#textTotalConcepto_guc").val(totalConcepto);
    },
    activateEditForm:function()
    {
        $("#formEditarGrupoUnidadConcepto").removeClass('hidden');
        $("#btn-actions-guc-concepto").addClass('hidden');
        $("#btn-actions-guc-concepto-save").removeClass('hidden');
        $("#formEditarGrupoUnidadConcepto input:text").removeAttr('disabled');
    },
    cancelEditForm:function()
    {
        $("#btn-actions-guc-concepto").removeClass('hidden');
        $("#btn-actions-guc-concepto-save").addClass('hidden');
        $("#formEditarGrupoUnidadConcepto").addClass('hidden');
        $("#formEditarGrupoUnidadConcepto input:text").attr('disabled','true');
    },
    cancelTableList:function()
    {
        $("#panelListGrupoUnidadConcepto, #btn-actions-guc-concepto, #btn-actions-guc-concepto-nuevo").addClass("hidden");
        $("#panelListGrupoUnidad, .frmGrupoUnidad").removeClass("hidden");

    }, 
    saveEditGrupoUnidadForm:function() //code
    {
        var idGrupoUnidad=$("#idGrupoUnidad_guc").val();
        idGrupoIngreso=$("#idGrupoIngreso").attr("data-idingreso");
        idGrupoIngreso=(idGrupoIngreso!=undefined)?idGrupoIngreso:'';

        formdata=$("#formEditarGrupoUnidadConcepto").serializeArray();
        formdata.push({name:'about',value:'updateGrupoUnidadConcepto'});
        formdata.push({name:'idGrupoUnidad',value:idGrupoUnidad});
        formdata.push({name:'month',value:GridIngreso.mesUnidadSelected});
        formdata.push({name:'year',value:DateControls.yearSelected});
        formdata.push({name:'idUnidadPadre',value:GridIngreso.idUnidadSelected});
        formdata.push({name:'idUnidadAsociada',value:GridIngreso.idUnidadAsociada});
        formdata.push({name:'ingresoId',value:idGrupoIngreso});

        $.post(this.saveAction,formdata,function(data){
            //console.log(data);
            if(data.response='success'){
                LoadDataIngreso.general();
                LoadDataIngreso.itemsConcepto();
                GridIngreso.listGrupoUnidades(GridIngreso.mesUnidadSelected,DateControls.yearSelected,GridIngreso.idUnidadSelected);
                actionGrupoUnidades.viewDetalleConcepto(idGrupoUnidad);
                GridIngreso.reloadGrid({year: DateControls.yearSelected});
                $("#btnSaveEditGrupoUnidadConcepto").removeAttr("disabled");
                $(TableConceptosGrupoUnidad.idTable+" input[type='checkbox']").attr('checked', false);
                $("#formEditarGrupoUnidadConcepto").addClass("hidden");
                $("#btn-actions-guc-concepto, #btn-actions-guc-concepto-save").addClass("hidden");
                alerta('Lista de Pagos',data.message,data.tipo);
            }else{
                alerta('Lista de Pagos',data.message,data.tipo);
            }       
        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar actualizar el registro','error');
        });
    }
}

TableConceptos={
    idTable:'#tblConceptos',
    readAction:'finanzas/ingreso/read',
    saveAction:'finanzas/ingreso/save',
    deleteAction:'finanzas/ingreso/delete',
    checkBoxSelected:function(id){
        inputSelectedId = 'TC_chkCol'+id;

        if($("#"+inputSelectedId).is(':checked')){
            $(this.idTable+" input[type='checkbox']").each(function(index,value){
                idCurrentInput=$(value).attr('id');
                if(idCurrentInput != inputSelectedId){
                    $("#"+idCurrentInput).attr('checked', false);
                } else{
                    if ($("#formNuevoConcepto").hasClass('hidden')) {
                        $("#btn-actions-concepto").removeClass('hidden');
                    }
                    $("#btn-actions-concepto-nuevo").addClass('hidden');
                    $("#btn-actions-concepto-save").addClass('hidden');
                    $("#btn-actions-concepto-delete").addClass('hidden');
                    $("#formEditarConcepto input:text").attr('disabled','true');
                }
            });
            TableConceptos.getCurrentRow(id);
        }else{
            $("#btn-actions-concepto-nuevo").removeClass('hidden');
            $("#btn-actions-concepto").addClass('hidden');
            $("#btn-actions-concepto-save").addClass('hidden');
            $("#co_conceptoId").val(0);
            $("#formEditarConcepto").addClass('hidden')[0].reset();
        }
    },
    getCurrentRow:function(id){
        tablerow=$("#TC_chkCol"+id).parent().parent().parent();
        totalConcepto=tablerow.children('td:eq(2)').html();
        nota=tablerow.children('td:eq(3)').children('span').attr('title');
        // setdata
        $("#co_conceptoId").val(id);
        $("#textTotalConcepto").val(totalConcepto);
        $("#ta_nota").html(nota);
    },
    activateNewConcepto:function(){
        $("#formNuevoConcepto").removeClass('hidden');
        $("#formNuevoConcepto")[0].reset();
        $("#selConcepto_nc").val(0);
        $("#selConcepto_nc").trigger('chosen:updated');
        $("#btn-actions-concepto-add").removeClass('hidden');
        $("#btn-actions-concepto-nuevo").addClass('hidden');
        $("#tblConceptos  .lbl").addClass('hidden');
    },
    cancelNewForm:function(){
        $("#formNuevoConcepto").addClass('hidden')[0].reset();
        $("#btn-actions-concepto-add").addClass('hidden');
        $("#btn-actions-concepto-nuevo").removeClass('hidden');
        $("#tblConceptos .lbl").removeClass('hidden');
    },
    saveNewConcepto:function(){
        formdata=$("#formNuevoConcepto").serializeArray();
        formdata.push({name:'about',value:'addConceptoIngreso'});
        formdata.push({name:'ingresoId',value:$("#co_ingresoId").val()});
        $.post(this.saveAction,formdata,function(data){
            if(data.tipo && data.mensaje){
                alerta('Conceptos de Ingreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    LoadDataIngreso.general();
                    LoadDataIngreso.itemsConcepto();
                    GridIngreso.reloadGrid({year: DateControls.yearSelected});
                    $("#formNuevoConcepto")[0].reset();
                    $("#btn-actions-concepto-add").addClass('hidden');
                    $("#formNuevoConcepto").addClass('hidden');
                    $("#btn-actions-concepto-nuevo").removeClass('hidden');
                }
            }else{
                alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar registrar un concepto.','error');
            }
        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar registrar un concepto.','error');
        });
    },
    activateEditForm:function(){
        $("#formEditarConcepto").removeClass('hidden');
        $("#btn-actions-concepto").addClass('hidden');
        $("#btn-actions-concepto-save").removeClass('hidden');
        $("#formEditarConcepto input:text").removeAttr('disabled');
    },
    cancelEditForm:function(){
        $("#btn-actions-concepto").removeClass('hidden');
        $("#btn-actions-concepto-save").addClass('hidden');
        $("#formEditarConcepto").addClass('hidden');
        $("#formEditarConcepto input:text").attr('disabled','true');
    },
    saveEditForm:function(){
        formdata=$("#formEditarConcepto").serializeArray();
        formdata.push({name:'about',value:'updateConceptoIngreso'});
        formdata.push({name:'ingresoId',value:$("#co_ingresoId").val()});
        formdata.push({name:'unidadId',value:$("#co_unidadId").val()});
        $.post(this.saveAction,formdata,function(data){
            if(data.tipo && data.mensaje){
                alerta('Conceptos de Ingreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    LoadDataIngreso.general();
                    LoadDataIngreso.itemsConcepto();
                    GridIngreso.reloadGrid({year: DateControls.yearSelected});
                    $("#formEditarConcepto")[0].reset();
                    $("#formEditarConcepto").addClass('hidden');
                    $("#btn-actions-concepto-save").addClass('hidden');
                    $("#btn-actions-concepto-nuevo").removeClass('hidden');
                }
            }else{
                alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar actualizar el registro','error');
            }        
        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar actualizar el registro','error');
        });
    },
    activateDelForm:function(){
        $("#btn-actions-concepto").addClass('hidden');
        $("#btn-actions-concepto-delete").removeClass('hidden');
    },
    cancelDelForm:function(){
        $("#btn-actions-concepto").removeClass('hidden');
        $("#btn-actions-concepto-delete").addClass('hidden');
    },
    deleteConcepto:function(){
        formData=new Array();
        formData.push({name:'ingresoId',value:$("#co_ingresoId").val()});
        formData.push({name:'unidadId',value:$("#co_unidadId").val()});
        formData.push({name:'conceptoId',value:$("#co_conceptoId").val()});
        formData.push({name:'about',value:'deleteConceptoIngreso'});
        $.post(this.deleteAction,formData,function(data){
            if(data.tipo && data.mensaje){
                alerta('Conceptos de Ingreso',data.mensaje,data.tipo);
                if(data.tipo=='informativo'){
                    LoadDataIngreso.itemsConcepto();
                    LoadDataIngreso.general();
                    GridIngreso.reloadGrid({year: DateControls.yearSelected});
                    $("#btn-actions-concepto-delete").addClass('hidden');
                    $("#btn-actions-concepto-nuevo").removeClass('hidden');
                }
            }else{
                alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar eliminar el concepto','error');
            }
        },'json').fail(function(){
            alerta('Lista de Pagos','Ocurrió un error desconocido en el servidor al intentar eliminar el concepto','error');
        });
    }
}


/**/
$('#selUnidad').on('change', function(){
    $("#totalMes").val('');
    if(this.value!=0){
        idPadre=$("#btnAgruparUnidad").attr("data-id");
        month=$("#modalGrupoUnidad #inputMonth").attr("data-month"); //code
        idGrupoIngreso=$("#idGrupoIngreso").attr("data-idingreso");
        idGrupoIngreso=(idGrupoIngreso!=undefined)?idGrupoIngreso:'';

        formData=new Array();
        formData.push({name:'about',value:'validateCuota'});
        formData.push({name:'idUnidad',value:this.value});
        formData.push({name:'month',value:month});
        formData.push({name:'year',value:DateControls.yearSelected});
        formData.push({name:'idPadre',value:idPadre});
        formData.push({name:'ingresoId',value:idGrupoIngreso});

        $.post(GridIngreso.readIngresoController, formData , function(data){
            if(data.response=='success'){
                $("#totalMes").val(data.totalMes);
                alerta('Ingreso', data.message, data.tipo);
                $("#btnAgruparUnidad").removeAttr("disabled");
            }else{
                alerta('Ingreso', data.message, data.tipo);
                $("#totalMes").val('');
                $('select').val(0).trigger("chosen:updated"); //reset select unidad
            }
        },'json').fail(function(){
            alerta('Ingreso', 'Ocurrio un problema interno en el servidor, vuelva a intentarlo.', data.tipo);
            $('select').val(0).trigger("chosen:updated"); //reset select unidad
        });
    }

})
/**/


$(".wrapperPagoMensual").hide();

$("#viewModalPagoTotal").click(function(e){
    e.preventDefault();
    Action.viewModalPagoTotal();
});

$("#viewModalListarPagos").click(function(e){
    e.preventDefault();
    Action.viewModalListarPagos();
});

$("#viewRecibo").click(function(e){
    alerta('Ingreso','Vista de recibo no disponible.','error')
    e.preventDefault();
});

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

// ventana concepto ingreso
$("#viewModalConceptos").click(function(e){
    e.preventDefault();
    Action.viewModalConceptos();
});

$("#viewModalUnidadesAgrupadas").click(function(e){
    e.preventDefault();
    Action.viewModalUnidadesAgrupadas();
});

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
    format:'dd-mm-yyyy'
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

$('#textTotalConcepto').priceFormat({
    prefix: '',
    thousandsSeparator: ''
});






/*$('.precio').priceFormat({
    prefix: '',
    thousandsSeparator: ''
});*/

$(".precio").keypress(function(e){
    if(e.which == 0) return true;
    if(e.which == 8) return true;
    if(e.which == 45) return true;                
    if(e.which < 46) return false;
    if(e.which > 46 && e.which<48) return false;
    if(e.which > 57 ) return false;
});



$("#slide-panel-ingreso").slideReveal({
  trigger: $("#trigger"),
  autoEscape: false,
  push: true,
  overlay: true,
  position: "right",
  'width':'30%'
});

//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridIngreso.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridIngreso.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-270);

    $(GridIngresoIP.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridIngresoIP.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-300);
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


/*
* Pago Total
* */
$("#btnSavePagoTotal").click(function(){
    $("#submitPagoTotal").click();
});
var $ValidatePT = $("#form-pagototal").validate({
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
        textFechaPago : { required : 'Ingresar la fecha de pago' },
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
        Action.registrarPagoTotal();
    },
    
});



/*
* Pago mensual.
* */
$("#btnSavePagoMensual").click(function(){
    $("#submitPagoMensual").click();
});


var $ValidatePM = $("#form-pagomensual").validate({
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
        textFechaPago : { required : 'Ingresar la fecha de pago' },
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
        Action.registrarPagoMensual();
    },
    
});

//show panel
var showPanel = {
    contentPanel:$("#contentPanel"),
    view:function(obj){
        panel=$(obj).attr("panel");
        this.contentPanel.hide();
        switch (panel) {
            case 'simple':
                $("#btnGenerarReporte, #formReporteIngreso, #btnReturnPanel").removeClass("hidden");
                break;
            case 'bcp':
                $("#btnGenerarReporteBCP, #formReporteIngresoBCP, #btnReturnPanel").removeClass("hidden");
                break;
            case 'return':
                this.contentPanel.show();
                $("#btnGenerarReporte, #btnGenerarReporteBCP, #formReporteIngreso, #formReporteIngresoBCP, #btnReturnPanel").addClass("hidden");
                break;
        }
    }
}


//validar registro de pago
$("#btnSaveEditPago").click(function(){
    $("#submit_EditPago").click();
});

$ValidateEP = $("#form-editarPago").validate({
    rules : {
        selBanco : { required : true },
        textNroOperacion : { required : true },
        textFechaPago : { required : true },
    },
    messages : {
        selBanco : { required : 'Seleccionar banco' },
        textNroOperacion : { required : 'Ingresar N° de operación' },
        textFechaPago : { required : 'Ingresar la fecha de pago' },
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
        TablePagos.save();
    },
    
});


//validar edicion de concepto.
$("#btnSaveEditConcepto").click(function(){
    $("#submit_EditConcepto").click();
});

$("#btnSaveEditGrupoUnidadConcepto").click(function(){
    $(this).attr("disabled","");
    $("#submit_EditGrupoUnidadConcepto").click();
});

$ValidateConcepto = $("#formEditarConcepto").validate({
    rules : {
        textTotalConcepto : { required : true },
    },
    messages : {
        textTotalConcepto : { required : 'Ingresar un total mayor a cero (0).' },
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
        error.insertAfter(element.siblings());
    },
    submitHandler:function(){
        TableConceptos.saveEditForm();
    },
});


//validate form grupo unidad concepto
$ValidateGrupoUnidadConcepto = $("#formEditarGrupoUnidadConcepto").validate({
    rules : {
        textTotalConcepto : { required : true },
    },
    messages : {
        textTotalConcepto : { required : 'Ingresar un total mayor a cero (0).' },
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
        error.insertAfter(element.siblings());
    },
    submitHandler:function(){
        TableConceptosGrupoUnidad.saveEditGrupoUnidadForm();
    },
});

//form add concepto

$("#btnAddConcepto").click(function(){
    $("#submit_NewConcepto").click();
});

$ValidateNuevoConcepto = $("#formNuevoConcepto").validate({
    rules : {
        selConcepto : { required : true },
        textTotalConcepto: { required: true},
    },
    messages : {
        selConcepto : { required : 'Seleccionar un concepto.' },
        textTotalConcepto : { required : 'Ingresar un total mayor a cero (0).' },
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
        TableConceptos.saveNewConcepto();
    },
});

//form add ingreso

$("#btnAddIngreo").click(function(){
    $("#submit_NewIngreso").click();
});

$ValidateNuevoIngreso = $("#formNuevoIngreso").validate({
    rules : {
        textFechaEmi : { required : true },
        textFechaVence: { required: true},
        selConcepto:{required:true},
        textTotalConceptoIngreso:{required:true}
    },
    messages : {
        textFechaEmi:{required:'Ingresar fecha de emision.'},
        textFechaVence:{required:'Ingresar fecha de vencimiento.'},
        selConcepto : { required : 'Seleccionar un concepto.' },
        textTotalConceptoIngreso : { required : 'Ingresar un total mayor a cero (0).'},
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
        Action.registrarIngreso();
    },
});

$("#or_Totales, #or_Detallado").click(function(){
    $("#wrapperConceptosRptPersonalizado").html('');
    $("#wrapper_or_Personalizado").removeClass('or_personalizado_active');
});



$("#or_Personalizado").click(function(){
    var formData=new Array();
    formData.push({name:'mes',value:$("#textMesEmision").val()});
    formData.push({name:'year',value:$("#textYearEmision").val()});
    formData.push({name:'about',value:'conceptosExistentesByProvision'});

    $.post(Action.readAction,formData,function(data){
        var htmlItemConcepto='<ul class="list-group" style="margin:0px!important;">  ';
        $.each(data,function(index,value){
            htmlItemConcepto+='<li class="list-group-item" style="padding:0px!important;"><div class="checkbox">'
            +'<label>'
            +'<input name="checkItemConceptoIngreso" value="'+value['id']+'" type="checkbox" class="ace">'
            +'<span class="lbl"> '+value['descripcion']+'</span>'
            +'</label>'
            +'</div></li>';
        });

        htmlItemConcepto+="</ul>";

        $("#wrapperConceptosRptPersonalizado").html(htmlItemConcepto);
        $("#wrapper_or_Personalizado").addClass('or_personalizado_active');


    },'json').fail(function(){
        alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
    });
});

$("#viewModalUnidadesAgrupadas").click(function(){
    $("#btnAgruparUnidad").attr("disabled","");
    $("#totalMes").val('');
    $("#panelListGrupoUnidad, .frmGrupoUnidad").removeClass("hidden");
    $("#panelListGrupoUnidadConcepto, #btn-actions-guc-concepto-nuevo, #formEditarGrupoUnidadConcepto, #btn-actions-guc-concepto-save, #btn-actions-guc-concepto").addClass("hidden");
});

$("#btnDescargar").click(function(){
    $("#textYearEmision").val(DateControls.yearSelected);
    $("#textMesEmision").val($("#co_currentmes").val());
    $("#or_Propietario").attr('checked', true);
    $("#or_Totales").attr('checked',true);
    $("#modalExportarOptions").modal('show');
    $("#modalExportarOptions .modal-dialog").css("width","600px");
});

$("#btnImportar").click(function(){
    $("#modalImportar").modal("show");
});

$("#btn-closeDetalle").click(function(){
    $("#pageTab_detalleIngresoParcial").hide();
    $("#pageTab_grid").show();
});


/*$("#modalConfirmUnidadAsociado").on('hidden.bs.modal', function (event) {
    $("#btnAgruparUnidad").removeAttr("disabled");
})*/


function uploadArchivo() {

    Action.fileConceptoName="";

    var file = $("#fileFormatoConceptosIngreso")[0].files[0];
    var name = file.name;
    var extension = name.split(".")[1];

    if(extension == "xls" || extension =="xlsx"){
        if(extension!="xlsx"){
            alerta('Ingreso','Intente subir un formato excel con versión posterior a 2003 (.xlsx).','advertencia');
            return false;
        }   
    }else{
        alerta('Ingreso','No se acepta archivos con este formato (.'+extension+')','advertencia');
        return false;
    }

    if(file){
        var fd = new FormData();
        fd.append("filexls", file);
        var xhr = new XMLHttpRequest();

        xhr.upload.addEventListener("progress", function(event){
          if (event.lengthComputable) {
            var percentComplete = Math.round(event.loaded * 100 / event.total);
            $(".progress").fadeIn(1000).find(".progress-bar").css("width",percentComplete+"%");
          }
        }, false);
        xhr.addEventListener("error", function(){
            alerta('Ingreso','La subida del archivo ha fallado.','advertencia');
        }, false);
        xhr.addEventListener("abort", function(){
            alerta('Ingreso','Se cancelara la subida del archivo.','advertencia');
        }, false);
        xhr.open("POST","finanzas/ingreso/upload");
        xhr.send(fd);
        xhr.onreadystatechange = function (aEvt) {
            if (xhr.readyState == 4) {
                if(xhr.status == 200){
                    var data = JSON.parse(xhr.responseText);
                    if(data.message == 'success'){
                        Action.fileConceptoName=data.file;
                        $(".progress").fadeOut(1000).find(".progress-bar").hide();

                        alerta('Excelente','El archivo se subio correctamente al servidor.','informativo');
                        setTimeout(function(){
                            $(".btn-registrar").fadeIn(2000).show();
                        },1000);
                    }else if(data.message == 'noformat'){
                        $(".progress").fadeOut(1000).find(".progress-bar").hide();
                        alerta('Formato no recomendado','Por favor intente subir archivos con formato .XLS ó .XLSX','advertencia');
                    }
                }else{
                    alerta('Error','Error al subir el archivo, por favor vuelva a intentarlo.','error');

                }
            }
        };
    }        
}