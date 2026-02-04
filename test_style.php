<?php
require 'vendor/autoload.php';
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', 'tbody,tr,td,style');
$purifier = new HTMLPurifier($config);

$html = '<tbody> <style>test</style> <tr><td>Content</td></tr> </tbody>';
echo "Result: " . $purifier->purify($html) . "\n";
