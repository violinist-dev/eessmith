jQuery(document).ready(function(){
    function openAccordion($el) {
        $el.next().slideToggle();
        $el.parent().toggleClass('panel-open');
    }

    jQuery('.cohesion-accordion .panel-heading').click(function(){
        openAccordion(jQuery(this));
    });

    jQuery('.cohesion-accordion .panel-heading').keypress(function(e){
        // On enter/return keypress, toggle accordion
        if(e.which == 13){
            openAccordion(jQuery(this));
        }

        // On space keypress, toggle accordion and prevent scrolling down page
        if(e.which == 32){
            openAccordion(jQuery(this));
            e.preventDefault();
        }
    });

    jQuery('.cohesion-accordion .panel-heading a').click(function(e){
        e.stopPropagation();
    });
});