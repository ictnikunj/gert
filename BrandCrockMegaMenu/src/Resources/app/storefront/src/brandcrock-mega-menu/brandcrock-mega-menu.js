$(document).ready(function(){
    $(document).on('mouseover','.nav-link', function() {
  
        if($(this).data('image')){
            $('.navigation-flyout-teaser-image-container').removeClass('hide');
       
            $('.navigation-flyout-teaser-image').attr('srcset',$(this).attr('data-image').replace());
        }
       
    });
});