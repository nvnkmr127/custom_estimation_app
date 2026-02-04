<?php
$html = '{IF_room_based}
    <div class="avoid-break">
        <h2>Investment Distribution</h2>
        <img src="{CHART_SECTIONS_PIE}" style="max-width: 100%; height: auto; width: 300px;">
    </div>
    {ENDIF}
    {IF_room_based == 0}
        <p>Flat list</p>
    {ENDIF}';

$scopeVars = ['room_based' => 1, '_raw_room_based' => 1];

// 1. IF_NOT logic
$html = preg_replace_callback('/\{IF_NOT_([a-zA-Z0-9_]+)\}(.*?)\{(?:ENDIF|END_IF)\}/s', function ($matches) use ($scopeVars) {
    echo "IF_NOT found: " . $matches[1] . "\n";
    return "";
}, $html);

// 2. Generic IF logic
$html = preg_replace_callback('/\{IF_([a-zA-Z0-9_]+)(?:\s*(==|!=|>=|<=|>|<)\s*([a-zA-Z0-9_]+))?\}(.*?)\{(?:ENDIF|END_IF)\}/s', function ($matches) use ($scopeVars) {
    $key = $matches[1];
    $operator = $matches[2] ?? null;
    $compareValue = $matches[3] ?? null;
    $content = $matches[4];

    echo "IF found: $key $operator $compareValue\n";

    $actualValue = $scopeVars[$key] ?? null;

    if (!$operator) {
        $isTrue = !empty($actualValue) && $actualValue != 0;
    } else {
        if ($operator === '==') {
            $isTrue = (string) $actualValue == (string) $compareValue;
        } elseif ($operator === '!=') {
            $isTrue = (string) $actualValue != (string) $compareValue;
        } else {
            $isTrue = false;
        }
    }

    return $isTrue ? $content : '';
}, $html);

echo "\nResult:\n" . $html . "\n";
