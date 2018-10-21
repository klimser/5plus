var Menu = {
    id: null,
    editItem: function(e) {
        var editForm = $("#edit_element_form");
        var src = $(e);
        if (src.data("webpage")) {
            editForm.find("#menuitem-webpage_id").val(src.data("webpage"));
            editForm.find(".field-menuitem-webpage_id").removeClass("disabled-group");
            editForm.find(".field-menuitem-url").addClass("disabled-group");
        } else {
            editForm.find("#menuitem-url").val(src.data("url"));
            editForm.find(".field-menuitem-url").removeClass("disabled-group");
            editForm.find(".field-menuitem-webpage_id").addClass("disabled-group");
        }
        editForm.find("#menuitem-id").val(src.data("id"));
        editForm.find("#menuitem-title").val(src.data("title"));
        editForm.find("#menuitem-active").prop('checked', src.data("active") === 1);
        editForm.find("#menuitem-attr").val(src.data("attr"));
        editForm.find(".btn").text("Сохранить");

        editForm.removeClass("hidden");
    },
    deleteItem: function(itemId) {
        if (confirm("Вы уверены, что хотите удалить элемент меню?")) {
            $.ajax({
                url: "/menu/delete-item",
                type: "post",
                dataType: "json",
                data: {
                    item_id: itemId
                },
                success: function(data) {
                    if (data.status === "ok") location.reload(true);
                    else {
                        alert("Error!");
                        console.log(data.message);
                    }
                }
            });
        }
    },
    setItemType: function(e, type) {
        var muted = (type === "url") ? "webpage_id" : "url";
        var container = $(e).closest(".menu-item-form");
        container.find(".field-menuitem-" + type).removeClass("disabled-group");
        container.find(".field-menuitem-" + muted).addClass("disabled-group");
    }
};