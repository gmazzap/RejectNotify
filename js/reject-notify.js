(function ($, RN_Data, ajaxurl, document) {

    "use strict";

    // Namespace
    var GMRejectNotify = {};


    /**
     * Append debug info to output message on modal
     * @param {object} data
     * @param {object} container
     * @param {string} errMessage
     * @returns {void}
     */
    GMRejectNotify.debug_info = function (data, container, errMessage) {
        container.append('<h3>' + RN_Data.debug_info + ':</h3><ul></ul>');
        var $ul = container.children('ul').eq(0);
        if (errMessage) {
            $ul.append('<li>' + errMessage + '</li>').show();
        }
        if (data) {
            if (data.sender) {
                $ul.append('<li><em>' + RN_Data.sender + '</em>: ' + data.sender + '</li>');
            }
            if (data.recipient) {
                $ul.append('<li><em>' + RN_Data.recipient + '</em>: ' + data.recipient + '</li>');
            }

            if (data.reason) {
                $ul.append('<li><em>' + RN_Data.email_content + '</em>: ' + data.reason + '</li>');
            }

            if (data.subject) {
                $ul.append('<li><em>' + RN_Data.email_subject + '</em>: ' + data.subject + '</li>');
            }
        }
    };


    /**
     * Output to modal
     * @param {object} data
     * @param {object} error
     * @returns {void}
     */
    GMRejectNotify.output = function (data, error) {
        $('#send_rejected_form_wrap').find('.loading').remove();
        var $container = $('#reject-notify-target'),
            htmlClass = data['class'];
        if (data && !error) {
            if (data.message && htmlClass) {
                $container.addClass(htmlClass).html('<strong>' + data.message + '</strong>');
                if (htmlClass === 'error' && RN_Data.debug === '1') {
                    GMRejectNotify.debug_info(data, $container, false);
                }
            } else {
                $container.addClass('error').html('<strong>' + RN_Data.def_mail_error + '</strong>');
                if (RN_Data.debug === '1') {
                    GMRejectNotify.debug_info(data, $container, RN_Data.ajax_wrong_data);
                }
            }
        }
        if (!data || error) {
            $container.addClass('error').html('<strong>' + RN_Data.def_mail_error + '</strong>');
            if (RN_Data.debug === '1') {
                GMRejectNotify.debug_info(false, $container, RN_Data.ajax_failed);
            }
        }
        $container.show();
    };

    $(document).ready(function () {

        // Open modal on button click
        $(document).on('click', '#send_reject_mail_box', function (e) {
            e.preventDefault();
            var postid = $(this).data('post'),
                tb_show_url = ajaxurl + '?action=' + RN_Data.action + '&postid=' + postid;
            if (!postid) {
                return false;
            }
            tb_show('', tb_show_url);
        });

        // Ajax send email on form submit
        $(document).on('submit', '#send_rejected_form_form', function (e) {
            e.preventDefault();
            var $form = $(this),
                formData = $form.serialize();
            $form.parent().append('<p class="loading">' + RN_Data.please_wait + '</p>');
            $form.remove();
            $.ajax({
                type:     "POST",
                url:      ajaxurl,
                data:     formData,
                dataType: "json"
            }).done(function (data) {
                GMRejectNotify.output(data, false);
                var already = '<strong>' + RN_Data.already_rejected + '</strong>';
                $('#send_reject_mail_box').parent().empty().html(already);
            }).fail(function () {
                GMRejectNotify.output(false, true);
            });
        });
    });


})(jQuery, RejectNotifyData, ajaxurl, document);