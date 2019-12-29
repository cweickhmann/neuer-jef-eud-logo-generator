// SVG.js
// Logo-Generator

(function () {
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
        document.querySelector('#btn_generate_package').addEventListener('click', generate_code, false);
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

    function generate_code(event) {
        var p = Pablo("#logo");
        console.log(p.markup());
    }

    document.addEventListener('DOMContentLoaded', setup, false);

}());
