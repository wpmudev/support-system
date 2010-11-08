/**
 * Support System plugin javascript
 * 
 * @author	S H Mohanjith <moha@mohanjith.net>
 * @since	1.5.4
 */

(function($) {
    $(document).ready(function() {
        $('p.vote_response a').click(function () {
            var vote = $(this).parent();
            var vote_content = $(vote).html();
            
            $.ajax({ url: $(this).attr('href'),
            success: function () {
                $(vote).html("Thank you for your feedback!");
            },
            beforeSend: function () {
                $(vote).html("Saving...");
            },
            error: function () {
                $(vote).html(vote_content);
            }});
            return false;
        })
        
        $('#incsub_support_fetch_imap').change(function() {
            if ($('#incsub_support_fetch_imap').val() == 'enabled') {
                $('.imap_details').show();
            } else {
                $('.imap_details').hide();   
            }
        });
        $('#incsub_support_fetch_imap').change();
    });
})(jQuery);
