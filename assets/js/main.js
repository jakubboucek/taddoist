$(()=>{
    const $copyField = $('.copy-field');

    $copyField.on('focus', (event)=>{
        console.log(event.target);
        $(event.target).select();
    });

    $copyField.each((id, element) => {
        const $field = $(element);
        const $tr = $field.closest('tr');
        const $button = $('a', $tr);
        $button.attr('href', $field.val());
    });

    $('.noclick').click((e)=>{
        alert('Na tlačítko neklikej, ale přetáhni jej do lišty záložek');
        e.preventDefault();
    });
});