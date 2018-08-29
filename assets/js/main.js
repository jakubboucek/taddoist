$(()=>{
    $('.copy-field').on('focus', (event)=>{
        console.log(event.target);
        $(event.target).select();
    });
});