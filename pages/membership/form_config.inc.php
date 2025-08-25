<?php

defined('INDEX_AUTH') or die('Direct access is not allowed!');

echo '<h3>Active Forms</h3>';

while ($data = $schemas->fetchObject()) {
    $option = json_decode($data->option ?? '');
    $formId = 'form_' . $data->id;
    $collapseId = 'collapse_' . $data->id;
    $caretId = 'caret_' . $data->id;

    echo <<<HTML
    <style>
        .caret {
            cursor: pointer;
            transition: transform 0.3s ease;
            display: inline-block;
            margin-left: 5px;
        }
        .caret.rotate {
            transform: rotate(90deg);
        }
        .collapse-box {
            display: none;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
        }
    </style>

    <h4 onclick="toggleCollapse('{$collapseId}', '{$caretId}')">
        <span id="{$caretId}" class="caret">&#9654;</span> {$data->name}
    </h4>
    <div id="{$collapseId}" class="collapse-box">
HTML;

    $form = new simbio_form_table_AJAX($formId, pluginUrl(reset: true), 'post');
    $form->submit_button_attr = 'name="saveData" value="' . __('Save') . '" class="s-btn btn btn-primary mt-2"';
    $form->table_attr = 'cellpadding="0" cellspacing="0" class="table"';
    $form->table_header_attr = 'class="alterCell"';
    $form->table_content_attr = 'class="alterCell2"';

    $form->addHidden('action', 'form_config');
    $form->addHidden('schema_id', $data->id ?? '');

    $form->addSelectList('form_config[image]', '<strong>Upload Profile Photo?</strong>', [[0, __('Disable')],[1, __('Enable')]], $option?->image ?? '', 'rows="1" class="imageWarning form-control col-2"');
    if (config('captcha')) {
        $form->addSelectList('form_config[captcha]', '<strong>Use Re-Captcha?</strong>', [[0, __('Disable')],[1, __('Enable')]], $option?->captcha ?? '', 'rows="1" class="form-control col-2"');
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
    $form->addSelectList('form_config[with_agreement]', '<strong>Save Data After Agreeing to Terms?</strong>', [[0, __('No')],[1, __('Yes')]], $option?->with_agreement ?? 0, 'rows="1" class="form-control col-2"');
    $form->addTextField('textarea', 'form_config[message_after_save]', '<strong>Message After Registration</strong>', $option?->message_after_save ?? '', 'rows="1" style="height: 80px" class="form-control"');

    echo $form->printOut();
    echo "</div>";
}
?>

<script>
function toggleCollapse(id, caretId) {
    const box = document.getElementById(id);
    const caret = document.getElementById(caretId);

    const isVisible = box.style.display === 'block';
    document.querySelectorAll('.collapse-box').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.caret').forEach(el => el.classList.remove('rotate'));

    if (!isVisible) {
        box.style.display = 'block';
        caret.classList.add('rotate');
    }
}

document.querySelectorAll('.imageWarning').forEach(el => {
    el.addEventListener('change', function() {
        if (this.value == 1) {
            let ask = confirm('Enabling this feature may make your system vulnerable to hacker attacks. Are you sure?');
            if (!ask) this.value = 0;
        }
    });
});
</script>
<style>
    .caret {
        cursor: pointer;
        transition: transform 0.3s ease;
        display: inline-block;
        margin-left: 20px;
    }
    .caret.rotate {
        transform: rotate(90deg);
    }
    .collapse-box {
        display: none;
        margin: 0 0 15px 15px;
        padding: 10px;
        border: 1px solid #ccc;
    }
</style>
