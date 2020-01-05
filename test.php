<?php
require_once __DIR__.'/php-svg-0.9.1/autoloader.php';

use SVG\SVG;

/*
// For debugging - Dump if you want to see what is being submitted
print_r($_POST);
echo "<br><br>";

if (isset($_POST['line'])) {
    echo "LOGO_SET: " . $_POST["logo_set"] . "<br>";
    echo "LINES: <br>";
    foreach ($_POST['line'] as $line) {
    echo htmlEntities($line, ENT_QUOTES) . "<br>";
    }
}
*/

/*
# Testing YAML as Config source
# Idea: Put one YAML per Logo set into assets/
echo "YAML...";
$parsed = yaml_parse_file("./assets/evil-template-1.yml");
echo "PARSED: " . print_r($parsed) . "\n";

print_r($parsed['config']['number_of_lines']);
print_r($parsed['config']['outputs']);

foreach($parsed['config']['outputs'] as $output) {
    print_r($parsed['config'][$output]);
}

echo "\n\n";
*/

/*
 * The following is a fairly weird hack to work around the
 * problem that php-svg does not properly overwrite styles
 * using SVGStyle::setStyle().
 * It adds the string provided to SVGStyle::setStyle and
 * places it CDATA-enclosed above the predefined style.
 * Intended behaviour!?
 * @TODO: File a bug/feature request! https://github.com/meyfa/php-svg/
 * 
 */
$svg = SVG::fromFile("./assets/evil-template-1.svg");
$doc = $svg->getDocument();
for ($i=0; $i<$doc->countChildren(); $i++) {
    if ($doc->getChild($i)->getName() != "style") { continue; }
    if ($doc->getChild($i)->getName() == "style" &&
        $doc->getChild($i)->getAttribute("id") == "dynamic") {
        $doc->removeChild($i);
        continue;
        }
    $style = $doc->getChild($i);
    $css_string  = ".star {fill: #FF0000; fill-opacity: 1}\n";
    $css_string .= ".line1 {fill: #00FF00; fill-opacity: 1}\n";
    $css_string .= ".line2 {fill: #0000FF; fill-opacity: 1}\n";
    $style->setCss($css_string);
    // echo $style->getAttribute("id");
}

header('Content-Type: image/svg+xml');
echo $svg;
?>
