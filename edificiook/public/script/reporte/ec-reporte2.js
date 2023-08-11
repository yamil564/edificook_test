var recibo = {

  month:null,
  year:null,
  type:null,
  idrow:null,
  filterx:null,
  admin:null,

  crearPdf:function()
    {
      $(".btn-estandar").find("img").addClass("hide");
      $(".modal-dialog").css("width","300px").css("margin","0 auto").css("margin-top","25%");
      $(".modal-dialog").css("width","300px");
      $("#myModalReciboProgress").modal('show');
      $.ajax({ 
        url: 'reporte/estado-cuenta/crear-pdf2',
        data: {
          'month':this.month,
          'year':this.year,
          'type':this.type,
          'idrow':this.idrow,
          'filterx':this.filterx,
          'admin':this.admin
        },
        dataType: "json",
        success: function(data){
          if(data.message = 'success'){
            $("#myModalReciboProgress").modal('hide');
            $(".btn-estandar").find("img").removeClass("hide");
            setTimeout(function(){
              recibo.descargardPdf(data.ruta, 'recibo-detallado.pdf');
            },1000);
          }
        },
        error: function(){
          console.log('error');
        },
        type: 'POST'
      });
    },

    descargardPdf:function (uri, name) {
      var link = document.createElement("a");
      link.download = name;
      link.href = uri;
      link.click();
    }

}
