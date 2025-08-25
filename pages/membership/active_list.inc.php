<?php
use SLiMS\Plugins;

defined('INDEX_AUTH') or die('Direct access is not allowed!');

// Fetch all active schemas
while ($data = $activeSchema->fetchObject()) {
    echo '<h3>' . htmlspecialchars($data->name) . '</h3>'; // Schema name as heading

    // Create new datagrid instance for each schema
    $datagrid = new simbio_datagrid();

    $structure = json_decode($data->structure, true);
    $structure = array_merge(array_values(array_filter($structure, function ($column) {
        return in_array($column['field'], ['member_id', 'member_name']);
    })), [['name' => _('Input Date'), 'field' => 'created_at']]);

    $columns = [];
    $columns[] = '`member_id` AS `Action`';

    foreach ($structure as $detail) {
        if ($detail['field'] === 'advance') continue;
        $columns[] = '`' . $detail['field'] . '` AS `' . $detail['name'] . '`';
    }

    // Table name derived from schema name
    $table_spec = 'self_registration_' . trim(str_replace(' ', '_', strtolower($data->name)));
    $datagrid->setSQLColumn(...$columns);
    $datagrid->setSQLorder('created_at DESC');

    // Set callback to render action buttons
    $datagrid->modifyColumnContent(0, 'callback{setButton}');
    $datagrid->table_attr = 'class="s-table table"';
    $datagrid->table_header_attr = 'class="dataListHeader thead-dark" style="font-weight: bold;"';
    $datagrid->chbox_form_URL = pluginUrl(reset: true);
    $datagrid->column_width = ['10%', '10%'];

    // Apply keyword filter if provided
    if (isset($_GET['keywords'])) {
        $keywords = $dbs->escape_string($_GET['keywords']);
        $datagrid->setSQLCriteria("(member_id LIKE '%$keywords%' OR member_name LIKE '%$keywords%')");
    }

    Plugins::getInstance()->execute('member_self_before_datagrid', [
        'datagrid' => $datagrid,
        'table_spec' => $table_spec
    ]);

    // Output datagrid result
    $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 10, false);
    echo $datagrid_result;

    echo '<hr>'; // Visual separator between schemas
}

// Action button render function
function setButton($dbs, $data)
{
    return '<a height="500" title="Detail ' . htmlspecialchars($data[2]) . '" href="' . pluginUrl(['section' => 'view_detail', 'member_id' => $data[0], 'headless' => 'yes']) . '" class="notAJAX openPopUp btn btn-primary"><i class="fa fa-pencil"></i></a>';
}
?>
