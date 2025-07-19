<?php

defined('INDEX_AUTH') or die('Direct access is not allowed!');

$data = $activeSchema->fetchObject();
$option = json_decode($data->option??'');

// create new instance
$form = new simbio_form_table_AJAX('mainForm', pluginUrl(reset: true), 'post');
$form->submit_button_attr = 'name="saveData" value="' . __('Save') . '" class="s-btn btn btn-default"';
// form table attributes
$form->table_attr = 'id="dataList" cellpadding="0" cellspacing="0"';
$form->table_header_attr = 'class="alterCell"';
$form->table_content_attr = 'class="alterCell2"';

$form->addHidden('action', 'form_config');
$form->addHidden('schema_id', $data?->id??'');

$list = [];
$list[] = [0, 'Select'];
while ($schemaData = $schemas->fetchObject()) {
    $list[] = [$schemaData->id, $schemaData->name];
}

$form->addSelectList('form_config[image]', '<strong>Upload Profile Photo?</strong>', [[0, __('Disable')],[1, __('Enable')]], $option?->image??'', 'rows="1" class="imageWarning form-control col-2"');
if (config('captcha')) {
    $form->addSelectList('form_config[captcha]', '<strong>Use Re-Captcha?</strong>', [[0, __('Disable')],[1, __('Enable')]], $option?->captcha??'', 'rows="1" class="form-control col-2"');
} else {
    $form->addAnything('<strong>Use Re-Captcha?</strong>', <<<HTML
    <p>You have not set up <em>Captcha</em>. Please enable it first by following these steps:</p>
    <ol>
        <li>Open the system module</li>
        <li>Select the Captcha Setting menu</li>
        <li>Configure it as needed. If you are unsure how to set it up, you can follow the tutorial in <a href="https://youtu.be/VLkdSRb7hE4">this video</a></li>
    </ol>
    HTML);
}
$form->addSelectList('form_config[with_agreement]', '<strong>Save Data After Agreeing to Terms?</strong>', [[0, __('No')],[1, __('Yes')]], $option?->with_agreement??0, 'rows="1" class="form-control col-2"');
$form->addTextField('textarea', 'form_config[message_after_save]', '<strong>Message After Registration</strong>', $option?->message_after_save??'', 'rows="1" style="height: 80px" class="form-control"');

echo $form->printOut();
?>
<script>
    $('.imageWarning').change(function() {
        if ($(this).val() == 1) {
            let ask = confirm('Enabling this feature may make your system vulnerable to hacker attacks. Are you sure?');

            if (!ask) {
                $(this).val(0)
                return 
            }
        }
    })
</script>
