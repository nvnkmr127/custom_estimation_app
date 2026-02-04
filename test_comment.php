<?php
require 'vendor/autoload.php';
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', 'div,tbody,tr,td');
$purifier = new HTMLPurifier($config);

$html = '<tbody> <!-- test comment --> <tr><td>Content</td></tr> </tbody>';
echo "Result: " . $purifier->purify($html) . "\n";
