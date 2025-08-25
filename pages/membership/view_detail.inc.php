<?php
use SLiMS\Plugins;

defined('INDEX_AUTH') or die('Direct access is not allowed!');

// Buffer the output so we can assign it to $content
ob_start();

// Loop through all active schemas
while ($schema = $activeSchema->fetchObject()) {
    $table_name = strtolower(trim(str_replace(' ', '_', $schema->name)));

    Plugins::getInstance()->execute('member_self_before_preview_detail', [
        'schema' => $schema,
        'table_name' => $table_name
    ]);

    // Fetch the member data from corresponding schema table
    $record = \SLiMS\DB::getInstance()->prepare(
        'SELECT * FROM self_registration_' . $table_name . ' WHERE member_id = ?'
    );
    $record->execute([$_GET['member_id'] ?? 0]);
    $data = $record->fetch(PDO::FETCH_ASSOC);


    if ($data) {
        echo 'hello';
        echo '<h3>' . htmlspecialchars($schema->name) . '</h3>';
        echo formGenerator($schema, $data, pluginUrl(['acc_member' => 'yes']));
        echo '<hr>';
    }
}

// If no data was found
if (!isset($data) || !$data) {
    echo '<p>No data found for member ID: ' . htmlspecialchars($_GET['member_id'] ?? '') . '</p>';
}

// Assign output to $content
$content = ob_get_clean();

// Include the template (this uses $content)
require SB . '/admin/' . $sysconf['admin_template']['dir'] . '/notemplate_page_tpl.php';
exit;
