var GridUsuario={
    usuarioController:"mantenimiento/usuario/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",
    currentRowIdSelected:null,
    init:function(){
        $(this.gridId).jqGrid({
            url: this.usuarioController,
            datatype: "json",
            mtype: "POST",
            colNames:['',"Normbre","Correo","Ultimo Acceso"],
            colModel:[
                {name:'id',index:'id', width:50,frozen:true,'formatter':GridUsuario.formatChk,search:false,sortable:false},
                {name:'nombre',index:'nombre', width:200,frozen:true,search:false,classes:'descripcion-unidad'},
                {name:'email',index:'email', width:200,frozen:true,search:false,classes:'descripcion-unidad'},
                {name:'ultimoacceso',index:'ultimoacceso', width:100,align:"right",sortable: false,hidden:true,search:false,'formatter':GridUsuario.formatearCelda,classes:'col-mes'},
            ],
            shrinkToFit: true,
            rowList: [],
            pgbuttons: false,
            pgtext: null,
            viewrecords: true,
            height: 'auto',
            loadonce:true,
            rowNum: 10000,
            pager : this.pageGridId,
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
    formatChk:function(cellvalue,options,rowObject){
        if(cellvalue=='' || cellvalue == null){
            return "";
        }
        if(cellvalue=='TE'){
            return '<center onclick="Action.toggleTotalIngreso()" sytle="cursor:pointer;"><i id="btn-dtIngreso" class="ui-icon ace-icon fa fa-plus"></i></center>';
        }
        return "<center><label class='position-relative tooltipmsg'><input type='checkbox' id='chkCol"+cellvalue+ "' name='chkCol"+cellvalue+"' class='ace' onclick='GridUsuario.checkBoxSelected("+cellvalue+");'/><span class='lbl'></span></label></center>";
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

GridUsuario.init();

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
    saveAction: 'mantenimiento/usuario/save',
    readAction: 'mantenimiento/usuario/read',
    deleteAction: 'mantenimiento/usuario/delete',
    ruta:$(".breadcrumb").text().trim(),
    getInfoUsuario:function(){
        
        $.post(this.readAction,{
            about:'getRowUsuario',
            id:GridUsuario.currentRowIdSelected,
        },function(data){
            $("#frmEdit_selTipo").val(data.tipo);
            $("#frmEdit_textNombre").val(data.nombre);
            $("#frmEdit_textApellido").val(data.apellido);
            $("#frmEdit_textCorreo").val(data.email);
            $("#frmEdit_textDni").val(data.dni);
            $("#frmEdit_textRuc").val(data.ruc);
            $("#frmEdit_textCelular").val(data.celular);
            $("#frmEdit_textTelefono").val(data.telefono);
            $("#frmEdit_textTelefonoOfi").val(data.telefonoOficina);
            $("#frmEdit_textFax").val(data.fax);
            $("#frmEdit_textRPL").val(data.rpl);
            $("#frmEdit_textDireccion").val(data.direccion);
            $("#frmEdit_textUsername").val(data.username);
            $("#frmEdit_textPass").val('');

            if(data.tipo =='PJ' ) {
                $("#frmEdit_labelNombre").html('Nombre Comercial');
                $("#frmEdit_labelApellido").html('Razón Social');
            }else{
                $("#frmEdit_labelNombre").html('Nombre');
                $("#frmEdit_labelApellido").html('Apellido');
            }


        },'json').fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intentar recuperar datos del usuario seleccionado.','error');
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

        $("#progressbar-securityPass").css('width', "0%");
        $("#rptaLevelPass").html('');

    },
    new:function(){
        Action.inicialStateFormNewUser();
        $("#modalNuevoUsuario").modal('show');
        $(".modal-dialog").css({'width': '50%', 'right': '0%'});
    },
    viewModalEditar:function(){
        Action.getInfoUsuario();
        $("#modalEditarUsuario").modal('show');
        $(".modal-dialog").css({'width': '50%', 'right': '0%'});

    },
    saveUsuario:function() {
        var form=$("#form-1, #form-2").serializeArray();
        form.push({name:'about',value:'saveNewUsuario'});

        $.post(this.saveAction,form,function(data){
            if(data.tipo) {
                alerta('Usuarios', data.mensaje, data.tipo);

                if (data.tipo == 'informativo') {
                
                    GridUsuario.reloadGrid(null);
                    $("#form-1")[0].reset();
                    $("#form-2")[0].reset();
                    $("#modalNuevoUsuario").modal('hide');
                }
            }else{
                alerta('Error','Ocurrió un problema en el servidor al intentar guardar los datos del nuevo usuario.','error');
            }

        },'json')
        .fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intentar guardar los datos del nuevo usuario.','error');
        });
    },
    saveEditarUsuario:function() {
        var form=$("#form-editarUsuario").serializeArray();
        form.push({name:'id',value:GridUsuario.currentRowIdSelected});
        form.push({name:'about',value:'saveEditUsuario'});
        $.post(this.saveAction,form,function(data){
            if(data.tipo) {
                alerta('Usuarios', data.mensaje, data.tipo);

                if (data.tipo == 'informativo') {
                    GridUsuario.currentRowIdSelected=null;
                    GridUsuario.reloadGrid(null);
                    $("#form-editarUsuario")[0].reset();
                    $("#modalEditarUsuario").modal('hide');
                }
            }else{
                alerta('Error','Ocurrió un problema en el servidor al intentar actualizar los datos del usuario.','error');
            }

        },'json')
        .fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intentar actualizar los datos del usuario.','error');
        });
    },

    confirmarDelete:function(){
        $("#modalConfirmarEliminacion").modal('show');
        $(".modal-dialog").css({'width':'30%'});
    },
    deteleUsuario:function(){
        if(GridUsuario.currentRowIdSelected==null){
            return;
        }
        
        $.post(this.deleteAction,{
            about:'deleteUser',
            id:GridUsuario.currentRowIdSelected,
        },function(data){
            if(data.tipo) {
                alerta('Usuarios', data.mensaje, data.tipo);

                if (data.tipo == 'informativo') {
                    GridUsuario.currentRowIdSelected=null;
                    GridUsuario.reloadGrid(null);
                    $("#modalConfirmarEliminacion").modal('hide');
                }
            }else{
                alerta('Error','Ocurrió un problema en el servidor al intentar eliminar el usuario seleccionado.','error');
            }

        },'json')
        .fail(function(){
            alerta('Error','Ocurrió un problema en el servidor al intentar eliminar el usuario seleccionado.','error');
        });
    },
}






var SeguridadPassword={
    numeros : "0123456789",
    letras : "abcdefghyjklmnñopqrstuvwxyz",
    letras_mayusculas : "ABCDEFGHYJKLMNÑOPQRSTUVWXYZ",

    tiene_numeros:function(texto){
        for(i=0; i<texto.length; i++){
            if (this.numeros.indexOf(texto.charAt(i),0)!=-1){
                return 1;
            }
        }
        return 0;
    },

    tiene_letras: function(texto){
        texto = texto.toLowerCase();
        for(i=0; i<texto.length; i++){
            if (this.letras.indexOf(texto.charAt(i),0)!=-1){
                return 1;
            }
        }
        return 0;
    },
    tiene_minusculas : function(texto){
        for(i=0; i<texto.length; i++){
            if (this.letras.indexOf(texto.charAt(i),0)!=-1){
                return 1;
            }
        }
        return 0;
    },
    tiene_mayusculas : function(texto){
        for(i=0; i<texto.length; i++){
            if (this.letras_mayusculas.indexOf(texto.charAt(i),0)!=-1){
                return 1;
            }
        }
        return 0;
    },
    verificar : function(clave){
        var seguridad = 0;
        if (clave.length!=0){

            if (clave.length <= 5){
                return 0;
            }

            if (SeguridadPassword.tiene_numeros(clave) && SeguridadPassword.tiene_letras(clave)){
                seguridad += 30;
            }

            if (SeguridadPassword.tiene_minusculas(clave) && SeguridadPassword.tiene_mayusculas(clave)){
                seguridad += 30;
            }

            if (clave.length >= 4 && clave.length <= 5){
                seguridad += 10;
            }else{
                if (clave.length >= 6 && clave.length <= 8){
                    seguridad += 30;
                }else{
                    if (clave.length > 8){
                       seguridad += 40;
                    }
                }
            }
        }
        return seguridad;            
    },
    getEstado:function(clave){
        var indicador=SeguridadPassword.verificar(clave);
        var estado='Desconocido';
    
        if(indicador==0){
            estado="Demasiado corta";
        }

        if(indicador>0 && indicador <30){
            estado="Débil";
        }
        if(indicador>=30 && indicador <60){
            estado="Bueno";
        }
        if(indicador>=60 && indicador <=100){
            estado="Óptima";
        }

        return estado;
    },
    generarPassword:function(){
        var cadena="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuioplkjhgfdsazxcvbnm";
        var longitudCadena=cadena.length;

        var longitudPass=7;
        var clave="";

        for(a=0;a<longitudPass;a++){
            clave+=cadena.charAt(parseInt(longitudCadena*Math.random(1)));
        }
        return clave;
    }
}












// ventana concepto ingreso
$("#viewModalConceptos").click(function(e){
    e.preventDefault();
    Action.viewModalConceptos();
});

$('.datepicker-right').datepicker({
    'language': 'es',
    autoclose: true,
    todayHighlight: true,
    format:'dd-mm-yyyy',
    orientation: 'right buttom'

});

$('.datepicker-default').datepicker({
    'language': 'es',
    autoclose: true,
    todayHighlight: true,
    format:'dd-mm-yyyy'
});

$('.fecha').mask('99-99-9999').val('dd-mm-aaaa');

$('#textTotalConcepto').priceFormat({
    prefix: '',
    thousandsSeparator: ''
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




//form add usuario

$("#btnCrearUsuario").click(function(){

    var error='';
    if($("#textNombre").val()=='' ){
        $("#textNombre").parent().addClass('has-error');
        error+=',textNombre';
    }else {
        $("#textNombre").parent().removeClass('has-error');
    }

    if( $("#textApellido").val()=='' ){
        $("#textApellido").parent().addClass('has-error');
        error+=',textApellido';
    }else {
        $("#textApellido").parent().removeClass('has-error');
    }


    if(!$("#wrapper-login").hasClass('hidden')){
        //validar password por defecto.
        if(!$("#wrapper-passwordDefault").hasClass('hidden')){
            //Asignar el tipo de contraseña a guardar
            $("#tipoPass").val('generado');
            if($("#textUsername").val()==''){  
                $("#textUsername").parent().addClass('has-error');
                error+=',textUsername';
            }else{
                $("#textUsername").parent().removeClass('has-error');
            }

            if($("#textPassDefault").val()==''){
                $("#textPassDefault").parent().addClass('has-error');
                error+=',textPassDefault';
            }else{
                $("#textPassDefault").parent().removeClass('has-error');
            }
        }

        //validar password personalizado.
        if(!$("#wrapper-passwordPersonalizado").hasClass('hidden')){

            //Asignar el tipo de contraseña a guardar
            $("#tipoPass").val('personalizado');

            if($("#textUsername").val()==''){  
                $("#textUsername").parent().addClass('has-error');
                error+=',textUsername';
            }else{
                $("#textUsername").parent().removeClass('has-error');
            }   

            if($("#textPassPerzo").val()==''){
                $("#textPassPerzo").parent().addClass('has-error');
                error+=',textPassPerzo';
            }else{
                $("#textPassPerzo").parent().removeClass('has-error');
            }

            if($("#textPassPerzo").val()!= $("#textRepetirPassPerzo").val() ){
                alerta("Usuarios","Las contraseñas no coinciden.","error");
                $("#textPassPerzo").parent().addClass('has-error');
                $("#textRepetirPassPerzo").parent().addClass('has-error');
                error+=',textPassPerzo';
            }else{
                $("#textPassPerzo").parent().removeClass('has-error');
                $("#textRepetirPassPerzo").parent().addClass('has-error');
            }
        }
    }else{
        $("#tipoPass").val('');
    }

    if(error!=''){
        return;
    }
    
    Action.saveUsuario();
});


//form edit usuario
$("#btnSaveEdit").click(function(){
    $("#submit_saveEditUsu").click();
});

$ValidateEditUser = $("#form-editarUsuario").validate({
    rules : {
        textNombre : { required : true },
        textApellido: { required: true},
    },
    messages : {
        textNombre:{required:'Este campo no puede quedar en blanco.'},
        textApellido:{required:'Este campo no puede quedar en blanco.'},
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
        Action.saveEditarUsuario();
    },
});




$("#frmEdit_selTipo").change(function(){
    if($(this).val() =='PJ'){
        $("#frmEdit_labelNombre").html('Nombre Comercial');
        $("#frmEdit_labelApellido").html('Razón Social');
    }else{
        $("#frmEdit_labelNombre").html('Nombre');
        $("#frmEdit_labelApellido").html('Apellido');
    }
});


$("#selTipo").change(function(){
    if($(this).val() =='PJ'){
        $("#labelNombre").html('Nombre Comercial');
        $("#labelApellido").html('Razón Social');
    }else{
        $("#labelNombre").html('Nombre');
        $("#labelApellido").html('Apellido');
    }
});



$("#btn-asignarDatosLogin").click(function(e){
    $("#wrapper-logintop").addClass('hidden');
    $("#wrapper-login").removeClass('hidden');
    $("#wrapper-loginfooter").removeClass('hidden');
    $("#textPassDefault").val(SeguridadPassword.generarPassword);
    e.preventDefault();
});

$("#btn-omitirDatosLogin").click(function(e){
    $("#wrapper-logintop").removeClass('hidden');
    $("#wrapper-login").addClass('hidden');
    $("#wrapper-loginfooter").addClass('hidden');
    
    $("#textUsername").val('');
    $("#textPassDefault").val('');
    $("#textPassPerzo").val('');
    $("#textRepetirPassPerzo").val('');

    $("#progressbar-securityPass").css('width', "0%");
    $("#rptaLevelPass").html('');

    e.preventDefault();
});


$("#btn-verInfoAdicional").click(function(){
    $("#form-1").addClass('hidden');
    $("#form-2").removeClass('hidden');

    $("#wrapper-btnVerInforAdicional").addClass('hidden');
    $("#wrapper-btnNextBack").removeClass('hidden');
});

$("#btn-backForm").click(function(){
    $("#form-1").removeClass('hidden');
    $("#form-2").addClass('hidden');

    $("#wrapper-btnVerInforAdicional").removeClass('hidden');
    $("#wrapper-btnNextBack").addClass('hidden');
});


$("#btn-definirPass").click(function(e){
    $("#wrapper-passwordDefault").addClass('hidden');
    $("#wrapper-passwordPersonalizado").removeClass('hidden');

    $("#textPassDefault").val('');
    $("#textPassPerzo").focus();
    
    e.preventDefault();
});

$("#btn-generarPasswordAuto").click(function(e){
    $("#wrapper-passwordDefault").removeClass('hidden');
    $("#wrapper-passwordPersonalizado").addClass('hidden');
    $("#textPassDefault").val(SeguridadPassword.generarPassword);
    $("#textPassPerzo").val('');
    $("#textRepetirPassPerzo").val('');
    $("#progressbar-securityPass").css('width', "0%");
    $("#rptaLevelPass").html('');

    e.preventDefault();
});

$("#textPassPerzo").keyup(function(){
    //var PassValue=$("#textPassPerzo").val();
    var prctSeguridad=SeguridadPassword.verificar($(this).val());
    var estadoSeguridad=SeguridadPassword.getEstado($(this).val());

    if($(this).val()==''){
        $("#progressbar-securityPass").css('width', "0%");
        $("#rptaLevelPass").html('');
    }else{
        $("#progressbar-securityPass").css('width', prctSeguridad+"%");
        $("#rptaLevelPass").html(estadoSeguridad);
    }
    
});


$(".numero").keypress(function(e){
    if(e.which == 0) return true;
    if(e.which == 8) return true;
    if(e.which == 45) return true;                
    if(e.which < 46) return false;
    if(e.which > 46 && e.which<48) return false;
    if(e.which > 57 ) return false;
});
