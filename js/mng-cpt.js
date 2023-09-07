jQuery(document).ready(function($) {
  
  // Delete button ~ show yes/no	
	$('.btn-delete').on('click', function() {
		$(this).addClass('active');	
		$(this).next('.mng-hidden').css('visibility', 'visible');
	});

  // Delete button ~ hide yes/no
	$('.del-no').on('click', function() {
		$(this).parent().css('visibility', 'hidden');
		$(this).parent().prev().removeClass('active');
	});

  // Enable Gutenberg
  $('.col-gutenberg input[type="checkbox"]').on('click', function(e) {
    const cpt = $(this).attr('data-cpt-name');
    const url = $(this).attr('data-gutenberg-url');
    window.location.href= url;
    e.stopPropagation();
  });

  $('.col-gutenberg label').on('click', function(e) {
    e.stopPropagation();
  });

});


document.addEventListener('DOMContentLoaded', function() {

  const appContainers = document.querySelectorAll('.rename-container');
  
  appContainers.forEach(appContainer => {
    
    new Vue({
      el: appContainer,
      data: {
        textInput: '',
        staticUrlPart: appContainer.dataset.staticUrl,
        hasTextClass: false
      },
      computed: {
        dynamicUrl() {
          return this.staticUrlPart + this.textInput;
        },
        linkUrl() {
          return this.staticUrlPart + '/' + this.dynamicUrl;
        },
        isLinkDisabled() {
          return this.textInput === '';
        }
      },
      watch: {
        textInput(newValue) {
          this.hasTextClass = newValue !== '';
        }
      }
    });
  });
  
});