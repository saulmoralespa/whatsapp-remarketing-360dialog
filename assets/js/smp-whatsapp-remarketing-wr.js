(function($){
    $('form#smp-whatsapp-remarketing-wr-config').submit(function (e){
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url:  ajaxurl,
            data: $(this).serialize() + '&action=smp_whatsapp_remarketing_wr_config',
            dataType: "json",
            beforeSend: function(){
                Swal.fire({
                    title: 'Guardando cambios',
                    didOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false
                });
            },
            success: function(r){
                if (r.status){
                    Swal.fire({
                        icon: 'success',
                        text: 'Cambios guardados',
                        showCloseButton: true,
                        showConfirmButton: true
                    });
                }else{
                    Swal.fire({
                        icon: 'error',
                        text: '¡Se ha producido un error, por favor inténtalo nuevamente!'
                    });
                }
            }
        });
    });

    $('form#smp-whatsapp-remarketing-wr-scheduler').submit(function (e){
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url:  ajaxurl,
            data: $(this).serialize() + '&action=smp_whatsapp_remarketing_wr_scheduler',
            beforeSend: function(){
                Swal.fire({
                    title: 'Guardando cambios',
                    didOpen: () => {
                        Swal.showLoading()
                    },
                    allowOutsideClick: false
                });
            },
            success: function(r){
                Swal.fire({
                    icon: 'success',
                    text: 'Cambios guardados',
                    showCloseButton: true,
                    showConfirmButton: true
                });
            }
        });
    });

    const selectEl = 'select.smp_whatsapp_remarketing_wr_template';


    function selectRefresh() {
        $(selectEl).select2({
            placeholder: 'Seleccione una opción',
            allowClear: true
        });
    }

    $(selectEl).each(function (e){
        let text = $(this).data("selected");
        $(this).find(`option[value=${text}]`).prop('selected', 'selected').change()
    });

    let schedulerContainer = $('#smp-whatsapp-remarketing-wr-scheduler');

    let cont = $(selectEl).length > 1 ? parseInt($(selectEl).last().attr("name").replace(/[^0-9]/g, '')) + 1 : 0;

    schedulerContainer.find('.add').click(function (){

        let select = schedulerContainer.find('tbody').find('select.smp_whatsapp_remarketing_wr_template');

        let fieldBoxSize = `<tr>
                                <td>
                                    <input type="checkbox" class="chosen_box">
                                </td>
                                <td>
                                    <select name="smp_whatsapp_remarketing_wr[${cont}][template]" class="smp_whatsapp_remarketing_wr_template" required>
                                        ${select.html()}
                                    </select>
                                </td>
                                <td>
                                    <input type="number" min="1" name="smp_whatsapp_remarketing_wr[${cont}][days]" required>
                                </td>
                            </tr>`;
        schedulerContainer.find('tbody').append(fieldBoxSize);
        selectRefresh();

        cont++;
    });


    schedulerContainer.on('click', '.remove', function (){

        schedulerContainer.find('.chosen_box:checked').each(function () {
            $(this).parent().parent('tr').remove();
        })
    });

    $(document).ready(function() {
        selectRefresh();
    });

})(jQuery);