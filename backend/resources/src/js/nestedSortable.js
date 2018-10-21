var NestedSortable = {
    init: function (selector) {
        $(selector).nestedSortable({
            handle: 'div',
            items: 'li',
            toleranceElement: '> div',
            tabSize: 10,
            isTree: true,
            expandOnHover: 700,
            forcePlaceholderSize: true,
            helper: 'clone',
            opacity: .6,
            placeholder: 'placeholder',
            tolerance: 'pointer',
            startCollapsed: false,
            relocate: function () {
                $.ajax({
                    url: $(this).data('callbackUrl'),
                    type: "post",
                    dataType: "json",
                    data: {
                        ordered_data: $(this).nestedSortable("toArray")
                    },
                    success: function (data) {
                        if (data.status !== "ok") {
                            Main.throwFlashMessage('#message_board', data.message, 'alert-danger');
                        }
                    }
                });
            }
        });
    }
};