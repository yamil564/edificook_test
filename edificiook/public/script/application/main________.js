
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