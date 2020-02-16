<?php
require_once __DIR__.'/php-svg-0.9.1/autoloader.php';

use SVG\SVG;

$asset_roots = Array(
    "Evil Corp" => "evil-template-1",
    "EUD (einzeilig)" => "eud-template-1",
    "EUD (zweizeilig)" => "eud-template-2",
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
    if ($conf === NULL) {
        $err_msg = sprintf("Error parsing \"%s\". Invalid JSON syntax.", $fname);
        error_log( $err_msg );
        throw new ParseError( $err_msg );
    }
    return $conf;
    }

function tempdir($dir=false, $prefix='php') {
    $tempfile=tempnam(sys_get_temp_dir(),'');
    if (file_exists($tempfile)) { unlink($tempfile); }
    mkdir($tempfile);
    if (is_dir($tempfile)) { return $tempfile; }
	}

function load_config($logo_set) {
    global $asset_roots;
    // error_log("load_config: " . print_r($logo_set, True));
    $asset_root = "./assets/" . $asset_roots[$logo_set];
    return json_decode_file($asset_root . ".json");
}

function load_svg_template($logo_set) {
    global $asset_roots;
    // error_log("load_svg_template: " . print_r($logo_set, True));
    $asset_root = "./assets/" . $asset_roots[$logo_set];
    return SVG::fromFile($asset_root . ".svg");
}

function generate_svg_logo($logo_set, $logo_config, $lines, $bbox, $colour_set, $echo_svg=False) {
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
    $svg = load_svg_template($logo_set);
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
            // error_log("generate_svg_logo 1: " . print_r($colour_set, True));
            // error_log("generate_svg_logo 2: " . print_r($logo_config[$colour_set], True));
            foreach($logo_config[$colour_set]["svg-class"] as $tag => $cont) {
                $css_string .= "." . $tag . " {" . $cont . "}\n";
                }
            $style->setCss($css_string);
            // echo $style->getAttribute("id");
            }
    }
    
    for ($i = 0; $i < $logo_config["number_of_lines"]; $i++ ) {
        $doc->getElementById("text_line" . (string)($i+1) )->setValue($lines[$i]);
    }
    
    // echo "Viewbox: _" . $bbox["viewbox"] . "_\n";
    // print_r($doc->getViewBox());
    $doc->setAttribute("viewBox", $bbox["viewbox"]);
    $doc->setAttribute("x", $bbox["x"]); //  - $logo_config["bbox_padding"]["left"]
    $doc->setAttribute("y", $bbox["y"]); //  - $logo_config["bbox_padding"]["top"]
    $doc->setWidth($bbox["width"] + $logo_config["bbox_padding"]["left"] + $logo_config["bbox_padding"]["right"]);
    $doc->setHeight($bbox["height"] + $logo_config["bbox_padding"]["top"] + $logo_config["bbox_padding"]["bottom"]);
    
    if ($echo_svg) {
        header('Content-Type: image/svg+xml');
        echo $svg;
        }
    
    return $svg;
    }

function svg2png($tmp_dir, $template_name, $colour_conf) {
    $template_name = str_replace(".svg", "", $template_name);
    $command = "rsvg-convert -f png -a -z %d %s -o %s/%s_zoom%d_%s.png %s/%s.svg\n";
    // error_log( "colour_set[png]: " . print_r($colour_conf, True) );
    foreach ($colour_conf["png"] as $s) {
        $res = $s["resolution"];
        $nsuffix = $s["name_suffix"];
        if( $s["bg_alpha"] == 0. ) {
            $bg_color_flag = "";
        } else {
            $bg_color_flag = "-b \"" . $s["background"] . "\"";
        }
        $cmd_string = sprintf($command, $res, $bg_color_flag, $tmp_dir, $template_name, $res, $nsuffix, $tmp_dir, $template_name);
        error_log($cmd_string);
        system($cmd_string);
        }
    }

function svg2pdf($tmp_dir, $template_name) {
    $template_name = str_replace(".svg", "", $template_name);
    $command = "rsvg-convert -f pdf -o %s/%s.pdf %s/%s.svg\n";
    $cmd_string = sprintf($command, $tmp_dir, $template_name, $tmp_dir, $template_name);
    // echo $cmd_string;
    system($cmd_string);
    }

function copySpecialAssets($tmp_dir, $special_assets_list=Array()) {
    foreach ($special_assets_list as $fname) {
        copy("./assets/" . $fname, $tmp_dir . "/" . basename($fname));
        }
    }

function pack2zip($tmp_dir) {
	// ZIP everything in ./tmp/somefilename.zip
	$zip_tmp = './tmp/' . date('YmdHms') . bin2hex(openssl_random_pseudo_bytes(4)) . '.zip';
	// echo $zip_tmp;

	$zipArchive = new ZipArchive();
	if (!$zipArchive->open($zip_tmp, ZIPARCHIVE::CREATE)) { die("Failed to create archive\n"); }
	$zipArchive->addPattern( "(.*)", $tmp_dir, array("add_path" => "logo-pack/", "remove_path" => $tmp_dir ) );
	if (!$zipArchive->status == ZIPARCHIVE::ER_OK) { echo "Failed to write files to zip\n"; }
	$zipArchive->close();
	
	return $zip_tmp;
    }

if (isset($_POST['line'], $_POST['logo_set'], $_POST['bbox_x'], $_POST['bbox_y'], $_POST['bbox_width'], $_POST['bbox_height'])) {
    $logo_set = $_POST['logo_set'];
    $conf = load_config($logo_set);
    $tmp_dir = tempdir();
    $x = $_POST['bbox_x'] - $conf["config"]["bbox_padding"]["left"];
    $y = $_POST['bbox_y'] - $conf["config"]["bbox_padding"]["top"];
    $w = $_POST['bbox_width'] + $conf["config"]["bbox_padding"]["left"] + $conf["config"]["bbox_padding"]["right"];
    $h = $_POST['bbox_height'] + $conf["config"]["bbox_padding"]["top"] + $conf["config"]["bbox_padding"]["bottom"];
    $bbox = Array(
        "x" => floatval($x),
        "y" => floatval($y),
        "height" => floatval($h),
        "width" => floatval($w),
        "viewbox" => $x . " " . $y . " " . $w . " " . $h
        );
    
    // error_log("_POST['logo_set']: " . print_r($_POST['logo_set'], True));
    // error_log("conf[config][outputs]: " . print_r($conf["config"]["outputs"], True));
    
    foreach( $conf["config"]["outputs"] as $colour_set ) {
        $svg_fname = $conf["config"]["outfile_prefix"] . "_" . $colour_set . ".svg";
        // error_log("Main: " . $tmp_dir . "/" . $svg_fname . " colour_set: " . $colour_set);
        $svg = generate_svg_logo($logo_set, $conf["config"], $_POST['line'], $bbox, $colour_set, False);
        file_put_contents($tmp_dir . "/" . $svg_fname, $svg);
        svg2pdf($tmp_dir, $svg_fname);
        svg2png($tmp_dir, $svg_fname, $conf["config"][$colour_set]);
        copySpecialAssets($tmp_dir, $conf["config"]["special-assets"]["extra-files-for-archive"]);
    }
    $zip_tmp = pack2zip($tmp_dir);
    header('Content-Type: application/zip');
    readfile($zip_tmp);
    
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
