<!-- Reject & Notify form START -->
<div class="send_rejected_form" id="send_rejected_form_wrap" style="padding:20px;">
    <h2>{{{title}}}</h2>

    <form id="send_rejected_form_form" method="post">
        <h2>{{{nonce}}}</h2>
        <input type="hidden" name="action" value="{{{action}}}">
        <input type="hidden" name="postid" value="{{{postId}}}">
        <input type="hidden" name="post_title" value="{{{postTitle}}}">
        <input type="hidden" name="recipient" value="{{{recipient}}}">

        <p>
            <label for="reject-notify-message">{{{messageLabel}}}</label><br>
                    <textarea
                        id="reject-notify-message"
                        name="reason"
                        style="width:100%"
                        rows="5"
                        required>{{{message}}}</textarea>
        </p>

        <p>{{{button}}}</p>
    </form>
</div>
<div id="reject-notify-target" style="margin-top:10px;padding:15px;display:none"></div>
<!-- Reject & Notify form END -->
