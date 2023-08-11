
$(function($){
    $('.chosen-select').chosen({allow_single_deselect:true}); 
                //resize the chosen on window resize
    $(window)
        .off('resize.chosen')
        .on('resize.chosen', function() {
            $('.chosen-select').each(function() {
                var $this = $(this);
                $this.next().css({'width': $(".chosen-select").parent().width()});
            })
        }).trigger('resize.chosen');
});

$("#chEdificio").chosen().change(function(event) {
    $.post("application/index/change", {
        id: $(this).val()
    },
    function(data){
        if(data.response && data.route){
            if(data.response==true && data.route!=''){
                window.location.href=data.route+"";
            }
        }
    }, 'json').fail(function(){
        alerta('Sistema','Ocurrió un error desconocido en el servidor, por favor contactar al administrador','error');
    });
});

function alerta(titulo,texto,clase,tiempo)
{
    if(tiempo==undefined){
        tiempo=3000;
    }

    baseUrl=$("base").attr('href');
    if(clase == 'advertencia') clase = 'warning';
    else if(clase == 'confirmacion') clase = 'info';
    else if(clase == 'informativo') clase = 'success';
    else clase = 'error';
    $.gritter.add({
        title: titulo,
        text: texto,
        class_name: 'gritter-'+clase+ ' gritter-center',
        time: tiempo,
        image: baseUrl+'images/iconos/'+clase+'.png' 
    });
}

$('[data-toggle="tooltip"]').tooltip();

var ToolsPanel = {

    fileTime:null,
    textArchivo:null,

    generateCrep:function()
    {
        if (confirm('Estas seguro de generar el archivo CREP?')) {
           $("#m_recaudacion_bcp .r_success").show();
            $("#m_recaudacion_bcp .r_error").addClass("hidden");
            $("#m_recaudacion_bcp").modal('show');
            $("#m_recaudacion_bcp .modal-dialog").css({'width': '30%'});
            $.post("finanzas/crep/generate-crep", function(data){
                if(data.response){
                    $("#m_recaudacion_bcp").modal('hide');
                    $("#m_message_crep").modal('show');
                    $("#m_message_crep .modal-dialog").css({'width': '25%'});
                    ToolsPanel.fileTime=data.time;   
                }else{
                    $("#m_recaudacion_bcp").modal('hide');
                    alerta("Crep", data.mensaje, "advertencia");
                }
            },'json').fail(function(){
                $("#m_recaudacion_bcp .r_success").hide();
                $("#m_recaudacion_bcp .r_error").removeClass("hidden").html('<p>Ocurrio un error en el servidor.</p>');
            });
        }
    },

    downloadCrep:function()
    {
        var formato=$( "input[type=radio]:checked" ).val();
        if(formato=='rar'){
            window.open("temp/crep/CREP_"+ToolsPanel.fileTime+".txt.rar");
        }else{
            window.open("temp/crep/CREP_"+ToolsPanel.fileTime+".txt.gz");
        }
        $("#m_recaudacion_bcp").modal('hide');
    },

    viewImportarCrep:function()
    {
        var self=this;
        $("#table_grep tbody").html('');
        $("#m_cobranza_recaudacion").modal('show');
        $("#m_cobranza_recaudacion .modal-dialog").css({'width': '60%', 'right':'0'});
        $(".loadCrep").removeClass("hidden");
        this.changeEstadoCrep();
        setTimeout(function(){
            $(".loadCrep").addClass("hidden");
            self.getListFileRecaudacion();
        },1000);
    },

    getCrep:function(event)
    {   
        if(typeof event.target.files[0] === "undefined")return;
        var formData = new FormData($(".frm_file_crep")[0]);
        $.ajax({
            url: 'finanzas/crep/upload-crep',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function(msg){
                $(".loadCrep").removeClass("hidden");
            },
            success: function (data) {
                if(data.rpta){
                    ToolsPanel.getListFileRecaudacion();
                }else if(data.format=='invalid'){
                    alerta('Crep','Formato invalido.','advertencia');
                }else if(data.archive=='exist'){
                    alerta('Crep','El archivo ya existe.','advertencia');
                }else{
                    alerta('Error','Ocurrio un problema en el servidor. Intentelo nuevamente.','error');
                }
                $(".loadCrep").addClass("hidden");
            },
            error: function(){
                alerta('Error','Ocurrio un problema en el servidor. Intentelo nuevamente.','error');
            }
        });
    },

    changeEstadoCrep:function(){
        $.post("finanzas/crep/change-estado-crep", function(data){
            //console.log(data);
        }).fail(function(){
            alerta('Error','Ocurrio un problema en el servidor. Intentelo nuevamente.','error');
        });
    },

    getListFileRecaudacion:function()
    {
        $("#table_grep thead tr th label").addClass("hidden");
        //$(".loadCrep").removeClass("hidden");
        $("#table_grep tbody").html('');
        $.post("finanzas/crep/listarFileRecaudacion", function(data){
            contentTable='';
            $.each(data,function(index,value){
                excel='-';
                if(value['excel']!=null){
                    excel='<a href="file/resultado-cdpg/'+value['excel']+'" download="" class="btn btn-success btn-xs"><i class="fa fa-file-excel-o"></i> Descargar</a>';
                }
                check='';
                if(value['edificio']!='-'){
                    check='<label class="position-relative">\
                            <input type="checkbox" name="crep[]" class="ace">\
                            <span class="lbl"></span>\
                        </label>';
                }
                contentTable+='<tr identity="'+value['id']+'">\
                    <td class="center">'+check+'</td>\
                    <td><img src="images/iconos/file.png" width="35"></td>\
                    <td>'+value['archivo']+'</td>\
                    <td>'+value['fechaMovimiento']+'</td>\
                    <td>'+value['numeroCuenta']+'</td>\
                    <td>'+value['edificio']+'</td>\
                    <td>'+excel+'</td>\
                </tr>';
            });

            $("#table_grep tbody").append(contentTable);
            $(".loadCrep").addClass("hidden");
        },'json').fail(function(){
            alert('error');
            $(".loadCrep").addClass("hidden");
        });

    },

    deleteCrep:function()
    {
        var $checkboxes = $('#table_grep tr td input[type="checkbox"]');
        ToolsPanel.textArchivo = ($checkboxes.filter(':checked').length > 1)?'los archivos':'el archivo';

        $(".loadCrep").removeClass("hidden");
        $.post("finanzas/crep/delete-crep", {check:this.dataCheckSelect} , function(data){
            if(data.rpta){
                ToolsPanel.getListFileRecaudacion();
                alerta('Crep', ToolsPanel.textArchivo + ' se eliminados correctamente.', 'informativo');
                $("#btn_tools").addClass("hidden");
            }else{
                alerta('Crep', 'Ocurrio un problema al eliminador ' + ToolsPanel.textArchivo, 'error');
            }    
        },'json').fail(function(){
           alerta('Crep', 'Ocurrio un problema al eliminador ' + ToolsPanel.textArchivo, 'error');
        });
    },

    transferirCrep:function()
    {
        var $checkboxes = $('#table_grep tr td input[type="checkbox"]');
        ToolsPanel.textArchivo = ($checkboxes.filter(':checked').length > 1)?'los archivos':'el archivo';

        $(".loadCrep").removeClass("hidden");
        $.post("finanzas/crep/transferir-crep", {check:this.dataCheckSelect} , function(data){
            if(data.rpta){
                alerta('Crep', 'Los pagos se registraron correctamente.', 'informativo');
                ToolsPanel.getListFileRecaudacion();
                /*$.post("finanzas/crep/delete-register-crep", {file:data.hashCrep}, function(data){
                    if(data){
                        ToolsPanel.getListFileRecaudacion();
                    }
                },'json').fail(function(){
                    alert('error');
                }); */
            }else{
                //alerta('Crep', 'No se registró ningun pago.', 'advertencia');
                ToolsPanel.getListFileRecaudacion();
            }
            $(".loadCrep").addClass("hidden");
            $("#table_grep thead tr th label").addClass("hidden");
            $("#btn_tools").addClass("hidden");
            $("#table_grep tr td input:checkbox").prop('checked', false);
        },'json').fail(function(){
            $(".loadCrep").addClass("hidden");
            $("#table_grep thead tr th label").addClass("hidden");
            $("#btn_tools").addClass("hidden");
            $("#table_grep tr td input:checkbox").prop('checked', false);
            alerta('Crep', 'Ocurrió un error desconocido en el servidor al intentar leer '+ToolsPanel.textArchivo+'.', 'error');
        });
    },

    dataCheckSelect:function()
    {
        var checkedSelected = [];
        $("#table_grep tbody tr input:checked").each(function(){
              checkedSelected.push($(this).parent().parent().parent().attr("identity"));
        });
        return checkedSelected;
    }

}

$("#table_grep").delegate('tr td input:checkbox','click', function(){
    var $checkboxes = $('#table_grep tr td input[type="checkbox"]');
    var cantCheckBox = $checkboxes.filter(':checked').length;
    if(cantCheckBox > 1){
        $("#table_grep thead tr th label").removeClass("hidden");
    }else{
        $("#table_grep thead tr th label").addClass("hidden");
    }
});

$("#btnShowModal").click(function(){
    $("#idcrep").click();
});

$("#table_grep").delegate('tr td input:checkbox','click', function(){
    var cantCheck=$('#table_grep tr td input:checkbox').filter(':checked').length;
    if(cantCheck!=0){
        $("#btn_tools").removeClass("hidden");
    }else{
        $("#btn_tools").addClass("hidden");
    }
});

/*window.onbeforeunload = function(e) {
  console.log(e);
  console.log('reload');
};*/

/*$('#m_cobranza_recaudacion').on('hidden.bs.modal', function (event) {
    console.log(event);
})*/


$(document).on('click', 'th input:checkbox' , function(){
    var that = this;
    $(this).closest('table').find('tr > td:first-child input:checkbox')
    .each(function(){
        this.checked = that.checked;
        $(this).closest('tr').toggleClass('selected');
    });
});