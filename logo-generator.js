// SVG.js
// Logo-Generator

// (function () {
"use strict";

    const assets = new Map([
        [0, ["Evil Corp", "./assets/evil-template-1.svg", 2, null]],
        [1, ["EUD", "./assets/eud-template-1.svg", 2, null]],
        [2, ["JEF", "./assets/jef-template-1.svg", 4, "upper"]],
        ]);
    
    var p = new Pablo("#logo");
    var current_asset = null;

    function create_svg(asset) {
        console.log("create_svg {\n" + asset[1]);
        var p = new Pablo("#logo");
        p.load(asset[1], update_form_and_register_events, true);
        
        console.log("END create_svg }");
    }

    function setup() {
        create_svg(assets.get(0));
        register_events();
        update_form_and_register_events(assets.get(0));
        populate_logo_set();
        select_logo_set();
    }
    
    function update_form_and_register_events(cont, req) {
        if (cont.length == 0) {
            console.log("File not found or empty.");
            s = this.svg({width: 300, height: 200});
            s.text({x: 150, y: 100, style: "fill: #F00; text-anchor: middle"})
                .content("File empty or not found.");
            return;
        }
        
        console.log("Removing Elements and Unregistering Events...");
        for (var node of document.querySelectorAll(".line")) {
            node.remove();
        }
        
        var lines_container = document.querySelector("#lines_container");
        var p = Pablo("#logo");
        console.log(lines_container);
        p.find(".text_line").forEach( function(item, index, array) {
            var input_id = "input" + item.id.substring(4);
            console.log(input_id);
            var div = document.createElement("div");
            div.setAttribute("class", "line");
            var input = document.createElement("input");
            input.type = "text";
            input.className = "input_line";
            input.id = input_id;
            input.name = "line[]";
            var label = document.createElement("label");
            label.setAttribute("for", input_id);
            label.innerText = String(index+1) + ". Zeile";
            div.appendChild(label);
            div.appendChild(input);
            lines_container.appendChild(div);
        });

        for (node of document.querySelectorAll(".input_line")) {
            node.addEventListener("input", update_input_lines, false);
        }
    }

    function register_events() {
        console.log("Registering Elements...");
        document.querySelector('#logo_set').addEventListener('change', select_logo_set, false);
        document.querySelector('#btn_generate_package').addEventListener('click', request_package, false);
    }
    
    function check_lines_width(svg) {
        var within_limits = true;
        var EU_bbox = svg.find("#EuropaUnion").bbox();
        var svg_bbox = svg.bbox();
        var eu_width = EU_bbox["width"];
        var eu_xmax = EU_bbox["x"] + EU_bbox["width"];
        svg.find(".text_line").forEach( function(item, index, array) {
            if (Pablo(item).bbox()["width"] > eu_width) {
                within_limits = false;
            }
        });
        if (!within_limits && svg.find("#too_wide_box").length == 0) {
            console.log("Too wide!");
            svg.rect({x: eu_xmax, y: 0,
                      width: svg_bbox["width"]-eu_xmax,
                      height: svg_bbox["height"],
                      fill: "gray",
                      "fill-opacity": 0.15
            }).attr("id", "too_wide_box");
            svg.line({x1: eu_xmax, x2: eu_xmax,
                      y1: 0, y2: svg_bbox["height"],
                      "stroke": "gray",
                      "stroke-dasharray": "4",
                      "stroke-width": 2,
                      "stroke-opacity": .75
             }).attr("id", "too_wide_line");
        } else if (!within_limits && svg.find("#too_wide_box").length == 1) {
            svg.find("#too_wide_box")
               .attr("width", svg_bbox["width"]-eu_xmax)
               .attr("height", svg_bbox["height"])
        } else {
            console.log("Alright!");
            var col_too_wide_box = svg.find("#too_wide_box")
            if (col_too_wide_box.length == 1) {
                col_too_wide_box.remove();
                svg.find("#too_wide_line").remove();
            }
        }
    }

    function update_input_lines(event) {
        console.log("Updating lines...");
        var p = Pablo("#logo");
        p.find(".text_line").forEach( function(item, index, array) {
            var input_id = "input" + item.id.substring(4);
            var the_input = document.getElementById(input_id);
            switch (current_asset[3]) {
                case "upper":
                    the_input.value = the_input.value.toUpperCase();
                    break;
                case "lower":
                    the_input.value = the_input.value.toLowerCase();
                    break;
            }
            p.find("#" + item.id).content( the_input.value );
        });
        var bbox = p.find("svg#logo-canvas").bbox();
        p.find("svg#logo-canvas").viewbox([bbox.x, bbox.y, bbox.width, bbox.height]);
        
        if (current_asset[0] == "EUD") {
            check_lines_width(p.find("svg#logo-canvas"));
        }
    }

    function select_logo_set(event) {
        console.log("Selecting logo set...");
        // console.log(event);
        var new_asset_name = document.querySelector("#logo_set").value;
        console.log(new_asset_name);
        for (var item of assets) {
            if (item[1][0] == new_asset_name) {
                var new_asset = item[1];
                current_asset = new_asset;
            }
        }
        console.log(new_asset);
        create_svg(new_asset);
        update_form_and_register_events(new_asset);
    }

    function populate_logo_set() {
        var logo_set = document.querySelector('#logo_set');
        while (logo_set.length > 0) { logo_set.remove(0); }
        for (var v of assets) {
            var opt = document.createElement("option");
            opt.innerHTML = v[1][0];
            logo_set.add(opt);
        }
    }
    
    function request_package(event) {
        var p = Pablo("#logo");
        var svg = p.find("svg#logo-canvas");
        var bbox = svg.bbox();
        console.log(bbox);
        var form = document.getElementById("control_form");
        var data = GetMessageBody(form, bbox);
        var httpRequest = CreateRequestObj();
        try {
            httpRequest.open(form.method, "test.php", true);  // synchronous
            httpRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            httpRequest.responseType = 'blob'; // Blob!
            console.log(data);
            httpRequest.send(data);
        } catch (e) {
            alert("Cannot connect to the server!");
            return;
        }
        httpRequest.onload = function (e) {
            console.log("Läuft!\n", httpRequest);
            console.log(httpRequest.getResponseHeader("Content-Type"));
            saveBlob(httpRequest.response, "logo-set.zip");
        }
    }
    
    function CreateRequestObj() {
        // IE supports the XMLHttpRequest object, but not on local files.
        var forceActiveX = (window.ActiveXObject && location.protocol === "file:");
        if (window.XMLHttpRequest && !forceActiveX) {
            return new XMLHttpRequest();
        }
        else {
            try {
                return new ActiveXObject("Microsoft.XMLHTTP");
            } catch(e) {
                console.log("Something went wrong with 'ActiveXObject('Microsoft.XMLHTTP')'. Consider using an actual Browser rather than MSIE.")
            }
        }
    }

    // create HTTP request body form form data
    function GetMessageBody(form, bbox) {
        var data = "";
        for (var i = 0; i < form.elements.length; i++) {
            var elem = form.elements[i];
            if (elem.name) {
                var nodeName = elem.nodeName.toLowerCase ();
                var type = elem.type ? elem.type.toLowerCase () : "";
                // if an input:checked or input:radio
                // is not checked, skip it
                if (    nodeName === "input"
                    && (type === "checkbox" || type === "radio")) {
                    if (!elem.checked) { continue; }
                }
                
                var param = "";
                // select element is special, if no value is specified
                // the text must be sent
                if (nodeName === "select") {
                    for (var j = 0; j < elem.options.length; j++) {
                        var option = elem.options[j];
                        if (option.selected) {
                            var valueAttr = option.getAttributeNode ("value");
                            var value = (valueAttr && valueAttr.specified) ? option.value : option.text;
                            if (param != "") {
                                param += "&";
                            }
                            param += encodeURIComponent (elem.name) + "=" + encodeURIComponent (value);
                        }
                    }
                } else {
                    param = encodeURIComponent(elem.name) + "=" + encodeURIComponent(elem.value);
                }
                
                if (data != "") {
                    data += "&";
                }
                data += param;                  
            }
        }
        data += "&bbox_x=" + encodeURIComponent(bbox.x);
        data += "&bbox_y=" + encodeURIComponent(bbox.y);
        data += "&bbox_width=" + encodeURIComponent(bbox.width);
        data += "&bbox_height=" + encodeURIComponent(bbox.height);
        return data;
    }
    
    function saveBlob(blob, fileName) {
        var a = document.createElement('a');
        a.href = window.URL.createObjectURL(blob);
        a.download = fileName;
        a.dispatchEvent(new MouseEvent('click'));
    }
    
    
    document.addEventListener('DOMContentLoaded', setup, false);

// }());
