( function( $ ) {
    $('.rid-ipls').on('click', '.remove-row', function(){
        var $this = $(this);
        $this.closest('.repeatable-field').remove();
	});
    $('.rid-ipls').on('click', '.add-row', function(){
        var $this = $(this);
        var $pin = $this.closest('.pin');
        var $clone = $('.original-repeatable-field').clone();
        var $locale = $('.current_language_code').val();
        $clone.removeClass('original-repeatable-field').addClass('repeatable-field');
        $clone.find('.ip-col').find('input').val('');
        $clone.find('.language-right-col').find('select').val( $locale );
        $pin.before( $clone );
        $('.rid-ipls').find('.repeatable-field').each(function($index){
            $(this).find('.ip-col').find('input').attr('name', 'rid_ip_map_lang['+$index+'][ip]');
            $(this).find('.language-right-col').find('select').attr('name', 'rid_ip_map_lang['+$index+'][language]');
        });
    });
})( jQuery );