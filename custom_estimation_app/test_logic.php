<?php

$terms = null;
$nl2br_result = nl2br($terms ?? '');
$val = $nl2br_result;
$isTrue = !empty($val) && $val != 0 && $val !== '';

echo "Terms: " . json_encode($terms) . "\n";
echo "nl2br: " . json_encode($nl2br_result) . "\n";
echo "Val: " . json_encode($val) . "\n";
echo "IsTrue: " . ($isTrue ? 'true' : 'false') . "\n";

$variables = ['terms' => $val];
$key = 'terms';
$checkKey = '_raw_' . $key;
$retrieved_val = isset($variables[$checkKey]) ? $variables[$checkKey] : ($variables[$key] ?? null);

echo "Retrieved Val: " . json_encode($retrieved_val) . "\n";
$isTrueRetrieved = !empty($retrieved_val) && $retrieved_val != 0 && $retrieved_val !== '';
echo "IsTrueRetrieved: " . ($isTrueRetrieved ? 'true' : 'false') . "\n";
