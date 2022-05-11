/* global pagenow, typenow, adminpage */

(function ($) {
    if ('book' === typenow && 'edit-php' === adminpage && 'edit-book' === pagenow) {
        // we create a copy of the WP inline edit post function
        const $wp_inline_edit = inlineEditPost.edit;

        // and then we overwrite the function with our own code
        inlineEditPost.edit = function (id) {
            if (post_id < 0) {
                return;
            }

            // "call" the original WP edit function
            // we don't want to leave WordPress hanging
            $wp_inline_edit.apply(this, arguments);

            // now we take care of our business

            // get the post ID
            var post_id = 0;

            if (typeof id == "object") {
                post_id = parseInt(this.getId(id));
            }

            // define the edit row
            const $editRow = document.getElementById("edit-" + post_id);

            const isbn = document.getElementById("library-isbn-" + post_id).innerHTML;

            $editRow.querySelector('input[name="isbn"]').value = isbn.trim();
        };
    }


    $("#bulk_edit").on("click", function () {
        // define the bulk edit row
        const $bulkRow = $("#bulk-edit");

        // get the selected post ids that are being edited
        const $post_ids = [];

        $bulkRow
            .find("#bulk-titles")
            .children()
            .each(function () {
                $post_ids.push(
                    $(this)
                        .attr("id")
                        .replace(/^(ttle)/i, "")
                );
            });

        // get the custom fields
        const isbn = $bulkRow.find('input[name="isbn"]').val();

        // save the data
        $.ajax({
            url: ajaxurl, // this is a variable that WordPress has already defined for us
            type: "POST",
            async: false,
            cache: false,
            data: {
                action: "manage_wp_posts_using_bulk_quick_save_bulk_edit", // this is the name of our WP AJAX function that we'll set up next
                post_ids: $post_ids, // and these are the 2 parameters we're passing to our function
                isbn,
            },
        });
    });
})(jQuery);
