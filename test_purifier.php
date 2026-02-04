<?php
require 'vendor/autoload.php';

// Mock Laravel's clean function or use Purifier directly
// Since I don't have the full environment easily, I'll try to find the config or use a generic one.
// But I can just check the config file.

$config = include 'config/purifier.php';
$html = '{IF_room_based}
    <div class="avoid-break">
        <h2>Investment Distribution</h2>
        <img src="{CHART_SECTIONS_PIE}" style="max-width: 100%; height: auto; width: 300px;">
    </div>
    {ENDIF}';

// This is a guess at how clean() works in this project
echo "Testing HTML Purifier...\n";
// Actually, I'll just check if { and } are preserved.
?>