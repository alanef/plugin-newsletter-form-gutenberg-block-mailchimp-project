jQuery(function($) {
    $(document).ready(function(){        
        $('.wf-mc-submit').on('click',function(){
            var $parent = $(this).parent(); 
            var $this = $(this);
            $this.attr('disabled','disabled');
            var form_name = $(this).siblings('.wf-mc-name').val();
            var form_email = $(this).siblings('.wf-mc-email').val();
            
            $.ajax({
                url: wf_mailchimp_block.ajaxurl,
                method: 'POST',
                crossDomain: true,
                dataType: 'json',
                data: {
                  action:'wf_mailchimp_block_submit_form',
                  name:form_name,
                  email:form_email
                }
              }).success(function(response) {
                if(response.data == '1'){
                  alert($parent.data('success'));
                } else if(response.data == '2'){
                  alert($parent.data('error'));
                } else if(response.data == '3'){
                  alert($parent.data('duplicate'));
                } else if(response.data == '4'){
                  alert($parent.data('submit'));  
                } else{
                  alert('An unknown error occured.');  
                }
                $this.removeAttr('disabled');
              }).error(function(type) {
                alert('An unknown error occured.');  
              });
        });
    });
});
