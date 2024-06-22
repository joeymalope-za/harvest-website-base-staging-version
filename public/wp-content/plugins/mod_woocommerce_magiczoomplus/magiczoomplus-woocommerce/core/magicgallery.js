jQuery(document).ready(function(){
    jQuery('.MagicToolboxSelectorsContainer a').on('click touchend', function(e){
        jQuery(this).closest('.MagicToolboxContainer').find('div[data-slide-id]').hide();
        jQuery(this).closest('.MagicToolboxContainer').find('div[data-slide-id="'+jQuery(this).attr('data-slide-id')+'"]').show();
        jQuery(this).closest('.MagicToolboxContainer').find('.active-selector').removeClass('active-selector');
        jQuery(this).closest('.MagicToolboxContainer').find('.mgt-active').removeClass('mgt-active');
        jQuery(this).addClass('active-selector');
        e.preventDefault();
    })

})