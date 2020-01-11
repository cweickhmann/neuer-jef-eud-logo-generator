<?php
require_once __DIR__.'/php-svg-0.9.1/autoloader.php';

use SVG\SVG;

$asset_roots = Array(
    "Evil Corp" => "evil-template-1",
    "EUD" => "eud-template-1",
    "JEF" => "jef-template-1"
    );

function dump_post() {
    // For debugging - Dump if you want to see what is being submitted
    echo "LOGO_SET: " . $_POST["logo_set"] . "<br>";
    echo "LINES: <br>";
    foreach ($_POST['line'] as $line) {
    echo htmlEntities($line, ENT_QUOTES) . "<br>";
    }
    echo $_POST['bbox_x'] . "<br>\n";
    echo $_POST['bbox_y'] . "<br>\n";
    echo $_POST['bbox_width'] . "<br>\n";
    echo $_POST['bbox_height'] . "<br>\n";
}

function json_decode_file(string $fname, bool $assoc = TRUE, int $depth = 512, int $options = JSON_OBJECT_AS_ARRAY) {
    $cont = file_get_contents($fname);
    $conf = json_decode($cont, $assoc=$assoc, $depth=$depth, $options=$options);
    return $conf;
    }

function generate_logo($logo_set, $lines, $bbox, $set, $echo_svg=False) {
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
    global $asset_roots;
    
    $asset_root = "./assets/" . $asset_roots[$logo_set];
    $conf = json_decode_file($asset_root . ".json");
    $svg = SVG::fromFile($asset_root . ".svg");
    $doc = $svg->getDocument();
    
    for ($i=0; $i<$doc->countChildren(); $i++) {
        if ($doc->getChild($i)->getName() != "style") { continue; }
        if ($doc->getChild($i)->getName() == "style" &&
            $doc->getChild($i)->getAttribute("id") == "dynamic") {
            $doc->removeChild($i);
            $g = new \SVG\Nodes\Structures\SVGStyle();
            $doc->addChild($g, 1);
            $style = $doc->getChild(1);
            $css_string = "";
            foreach($conf["config"][$set] as $tag => $cont) {
                $css_string .= "." . $tag . " {" . $cont . "}\n";
                }
            $style->setCss($css_string);
            // echo $style->getAttribute("id");
            }
    }
    
    for ($i = 0; $i < $conf['config']['number_of_lines']; $i++ ) {
        $doc->getElementById("text_line" . (string)($i+1) )->setValue($lines[$i]);
    }
    
    // echo "Viewbox: _" . $bbox["viewbox"] . "_\n";
    // print_r($doc->getViewBox());
    $doc->setAttribute("viewBox", $bbox["viewbox"]);
    // $doc->setX($bbox["x"]);
    // $doc->setY($bbox["y"]);
    $doc->setWidth($bbox["width"]);
    $doc->setHeight($bbox["height"]);
    
    
    if ($echo_svg) {
        header('Content-Type: image/svg+xml');
        echo $svg;
        }
}

function svg2pdf() {

}

function pack2zip() {

}

if (isset($_POST['line'], $_POST['logo_set'], $_POST['bbox_x'], $_POST['bbox_y'], $_POST['bbox_width'], $_POST['bbox_height'])) {
    $bbox = Array(
        "x" => floatval($_POST['bbox_x']),
        "y" => floatval($_POST['bbox_y']),
        "height" => floatval($_POST['bbox_height']),
        "width" => floatval($_POST['bbox_width']),
        "viewbox" => $_POST['bbox_x'] . " " . $_POST['bbox_y'] . " " . $_POST['bbox_width'] . " " . $_POST['bbox_height']
        );
    generate_logo($_POST['logo_set'], $_POST['line'], $bbox, "RGB", True);
} else {
    http_response_code(500);
    echo $_POST['line'], $_POST['logo_set'], $_POST['bbox_x'], $_POST['bbox_y'], $_POST['bbox_width'], $_POST['bbox_height'];
}

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


?>
