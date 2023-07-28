jQuery(document).ready(function($) {
	
	$('.btn-delete').on('click', function() {
		$(this).addClass('active');	
		$(this).next('.mng-hidden').css('visibility', 'visible');
	});

	$('.del-no').on('click', function() {
		$(this).parent().css('visibility', 'hidden');
		$(this).parent().prev().removeClass('active');
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