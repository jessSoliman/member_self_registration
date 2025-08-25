<?php
use SLiMS\Url;

defined('INDEX_AUTH') or die('Direct access is not allowed!');

$schemas = getActiveSchemaData();

if (empty($schemas)) {
    throw new Exception("Tidak ada Skema yang aktif.");
}

$action = $_POST['action'] ?? $_GET['actuion'] ?? null;
if (!isset($opac)) $opac = $this;

// Get current path from URL
$currentPath = isset($_GET['p']) ? trim(strtolower($_GET['p'])) : null;

$found = false;

foreach ($schemas as $schema) {
    $schemaPath = trim(strtolower(str_replace(' ', '_', $schema->name)));

    if ($currentPath === null || $currentPath === $schemaPath) {
        // Run action if needed
        if ($action !== null) {
            action('save', ['schema' => $schema]);
        }

        // Display form
        echo formGenerator(
            $schema,
            actionUrl: Url::getSelf(fn($self) => $self . '?p=' . $schemaPath),
            opac: $opac
        );

        $found = true;
        break; // stop loop once matched
    }
}

if (!$found) {
    echo '<div class="alert alert-danger">Form not found or unavailable.</div>';
}
