<?php
use SLiMS\Table\Schema;

defined('INDEX_AUTH') or die('Direct access is not allowed!');

$columns = implode('', array_merge(
    array_map(function($item) {
        return '<option value="' . $item . '">' . $item . '</option>';
    }, array_values(array_filter(Schema::table('member')->columns(), function($column) {
        if (!preg_match('/(expire|regis|since|notes|input|last_|is_)/', $column)) return true;
    }))),
    ['<option value="advance">Advanced Field</option>']
));


// create new instance
$form = new simbio_form_table_AJAX('mainForm', pluginUrl(['section' => 'add_schema']), 'post');
$form->submit_button_attr = 'name="saveData" value="' . __('Save') . '" class="s-btn btn btn-default"';
// form table attributes
$form->table_attr = 'id="dataList" cellpadding="0" cellspacing="0"';
$form->table_header_attr = 'class="alterCell"';
$form->table_content_attr = 'class="alterCell2"';

$form->addHidden('action', 'create_schema');
$form->addTextField('text', 'name', '<strong>Name*</strong>', '', 'rows="1" class="form-control"');

$form->addAnything('<strong>Information</strong>', <<<HTML
<div class="d-flex flex-column">
    <label><strong>Form Title</strong></label>
    <input type="text" name="info[title]" class="form-control col-3"/>
    <label><strong>Others</strong></label>
    <p>Notification regarding prerequisites, pre/post registration follow-up information</p>
    <div id="editor" class="col-8">
        <div id="toolbarContainer"></div>
        <div id="contentDesc" class="rounded-lg px-3 noAutoFocus" style="background-color: white; min-height: 200px"></div>
    </div>
    <label><strong>Place</strong></label>
    <select class="form-control col-2" name="info[position]">
        <option value="top">Top</option>
        <option value="bottom">Bottom</option>
    </select>
</div>
HTML);

$form->addAnything('<strong>Structure</strong>', <<<HTML
<div class="d-flex flex-column">
    <label><strong>Section</strong></label>
    <p>Determine which fields will be filled in on the registration form later.</p>
    <hr>
    <div id="editableArea">
        <div class="d-flex flex-column col-12">
            <label id="label-1"><strong>Section <b id="columnName1"></b></strong></label>
            <div class="d-flex flex-row">
                <input type="text" class="columnName form-control col-4 noAutoFocus" data-label="1" name="column[1][name]" placeholder="Label that will appear on the form"/>
                <select class="form-control col-1 noAutoFocus" name="column[1][is_required]">
                    <option value="1">Required fields</option>
                    <option value="0">Optional</option>
                </select>
                <select class="form-control col-3 noAutoFocus" name="column[1][field]" data-row="1">
                    <option value="">Select Database Column</option>
                    {$columns}
                </select>
            </div>
            <div id="advForm1" class="d-none flex-column my-3">
                <div class="d-block">
                    <label><strong>Advanced Fields</strong></label>
                </div>
                <div class="d-flex flex-row">
                    <input type="text" class="form-control col-6 noAutoFocus" name="column[1][advfield]" placeholder="Column name in database"/>
                    <select class="form-control col-4 noAutoFocus" name="column[1][advfieldtype]">
                        <option value="">Select</option>
                        <option value="int">Number</option>
                        <option value="varchar">Short Text</option>
                        <option value="text">Paragraph Text</option>
                        <option value="enum">Dropdown List</option>
                        <option value="enum_radio">Radio List</option>
                        <option value="text_multiple">Multiple Choice</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <button row="1" class="addRow notAJAX btn btn-success btn-sm col-2 my-3">Add Next</button>
</div>
HTML);

echo $form->printOut();
?>
<script>
    let area = $('#editableArea')
    let addRow = $('.addRow')
    let template = `
    <div id="detailrow{column}" class="d-flex flex-column col-12">
        <label id="label-1"><strong>Field <b id="columnName{column}"></b></strong></label>
        <div class="d-flex flex-row">
            <input type="text" class="columnName form-control col-4 noAutoFocus" data-label="{column}" name="column[{column}][name]" placeholder="Label that will appear on the form"/>
            <select class="form-control col-1 noAutoFocus" name="column[{column}][is_required]">
                <option value="1">Required</option>
                <option value="0">Optional</option>
            </select>
            <select class="form-control col-3 noAutoFocus" name="column[{column}][field]" data-row="{column}">
                <option value="">Select Database Column</option>
                <?= $columns ?>
            </select>
            <button class="deleteRow notAJAX btn btn-danger" data-remove="{column}"><i class="fa fa-trash"></i></button>
        </div>
        <div id="advForm{column}" class="d-none flex-column my-3">
            <div class="d-block">
                <span><strong>Advanced Field</strong></span>
            </div>
            <div class="d-flex flex-row">
                <input type="text" class="form-control col-6 noAutoFocus" name="column[{column}][advfield]" placeholder="Column name in database"/>
                <select class="form-control col-4 noAutoFocus advFieldType" data-column="{column}" name="column[{column}][advfieldtype]">
                    <option value="">Select</option>
                    <option value="int">Number</option>
                    <option value="varchar">Short Text</option>
                    <option value="text">Paragraph Text</option>
                    <option value="enum">Dropdown List</option>
                    <option value="enum_radio">Radio List</option>
                    <option value="text_multiple">Multiple Choice</option>
                </select>
            </div>
            <div id="dropdownOptions{column}" class="dropdown-options mt-2 d-none">
                <label><strong>Dropdown Options</strong></label>
                <div class="dropdown-options-container"></div>
                <button type="button" class="btn btn-sm btn-info mt-1 addDropdownOption" data-column="{column}">Add Option</button>
            </div>
        </div>
    </div>`;


    addRow.click(function(e) {
        e.preventDefault()
        let nextNumber = parseInt($(this).attr('row')) + 1
        area.append(template.replace(/\{column\}/g, nextNumber))
        $(this).attr('row', nextNumber)
    })

    area.on('keyup', '.columnName', function(){
        let labelRow = $(this).data('label')
        $(`#columnName${labelRow}`).html($(this).val())
    })

    area.on('blur', '.columnName', function(){
        let labelRow = $(this).data('label')
        $(`#columnName${labelRow}`).html($(this).val())
    })

    area.on('change', 'select', function(){
        let column = $(this).data('row')

        if ($(this).val() === 'advance') {
            $(`#advForm${column}`).addClass('d-flex')
        } else {
            $(`#advForm${column}`).removeClass('d-flex')
            $(`input[name="column[${column}][advfield]"]`).val('')
            $(`select[name="column[${column}][advfieldtype]"]`).val('')
        }
    })

    area.on('click', '.deleteRow', function(){
        let column = $(this).data('remove')
        $(`#detailrow${column}`).remove()
    })

    area.on('click', 'input,select', function(e){
        e.preventDefault()
    })

    $(document).ready(function(){
        let editorInstance = '';

        $('#mainForm').submit(function(){
            // Existing editor content
            $(this).append('<textarea name="info[desc]" class="d-none">' + editorInstance.getData() + '</textarea>');

            // 👇 NEW: convert dropdown options
            $('.advFieldType').each(function(){
                let column = $(this).data('column');
                let type = $(this).val();

                if (type === 'enum') {Oh noOh no
                    let fieldName = $(`input[name="column[${column}][advfield]"]`).val().trim();
                    let options = [];
                    $(`input[name="column[${column}][options][]"]`).each(function(){
                        if ($(this).val().trim() !== '') {
                            options.push($(this).val().trim());
                        }
                    });

                    let advfieldValue = fieldName + ',' + options.join('|');

                    $(`input[name="column[${column}][advfield]"]`).val(advfieldValue);
                }
            });
        });


        DecoupledEditor
            .create(document.querySelector('#contentDesc'), {  
                toolbar: ['heading', 'bold', 'italic', 'link', 'numberedList', 'bulletedList']
            })
            .then(editor => {
                const toolbarContainer = document.querySelector('#toolbarContainer');
                toolbarContainer.appendChild(editor.ui.view.toolbar.element);
                editorInstance = editor;
            })
            .catch(error => {
                console.log(error);
            });

        // When the form is submitted, retrieve the content
        // and put it into a hidden textarea
        $('#mainForm').submit(function(){
            $(this).append('<textarea name="info[desc]" class="d-none">' + editorInstance.getData() + '</textarea>');
        });

        $('#dataList > tbody').prepend(`
        <tr>
            <td colspan="3">
                <div class="alert alert-warning" role="alert">
                    <h4 class="alert-heading">Warning</h4>
                    <p>The created scheme cannot be changed. Make sure everything is filled in correctly.</p>
                </div>
            </td>
        </tr>`);
    });

    area.on('change', '.advFieldType', function(){
        let column = $(this).data('column');
        let type = $(this).val();

        if (type === 'enum') {
            $(`#dropdownOptions${column}`).removeClass('d-none');
        } else {
            $(`#dropdownOptions${column}`).addClass('d-none').find('.dropdown-options-container').empty();
        }
    });

    area.on('click', '.addDropdownOption', function(){
        let column = $(this).data('column');
        let container = $(`#dropdownOptions${column} .dropdown-options-container`);
        let index = container.children().length + 1;

        container.append(`
            <div class="input-group mb-1 col-6">
                <input type="text" name="column[${column}][options][]" class="form-control form-control-sm" placeholder="Option ${index}"/>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger btn-sm removeDropdownOption">&times;</button>
                </div>
            </div>
        `);
    });

    area.on('click', '.removeDropdownOption', function(){
        $(this).closest('.input-group').remove();
    });


</script>