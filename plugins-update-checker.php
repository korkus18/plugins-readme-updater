<?php
// Nastavení časového pásma podle WordPressu
date_default_timezone_set(get_option('timezone_string') ?: 'Europe/Prague');

// Zabránění přímému přístupu k souboru
if (!defined('ABSPATH')) {
    exit;
}

function render_plugins_update_checker_page() {
    ?>
    <div class="wrap">
        <h2>Plugins Update Checker</h2>
        <pre><?php plugins_update_checker(); ?></pre>
    </div>
    <?php
}

function plugins_update_checker() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // ❗ Resetujeme cache WordPressu pro update pluginů
    delete_site_transient('update_plugins');

    // ❗ Vynutíme okamžitou kontrolu aktualizací pluginů
    wp_update_plugins();

    // ❗ Získáme nejnovější stav aktualizací
    $updates = get_site_transient('update_plugins');
    $plugins = get_plugins();
    $plugin_list = array();
    $i = 1;

    // Získání správného časového pásma z WordPressu
    $timezone = wp_timezone();

    $timezone_string = get_option('timezone_string');

    // Pokud není časové pásmo nastavené, použij Europe/Prague
    if (!$timezone_string) {
        $timezone_string = 'Europe/Prague';
    }

    // Vytvoření objektu časového pásma
    $timezone = new DateTimeZone($timezone_string);
    $datetime = new DateTime("now", $timezone);
    $revision_time = $datetime->format("Y-m-d H:i");


    // Uložení do pole
    $plugin_list["revision"] = $revision_time;

    // Pokud jsou dostupné aktualizace, uložíme je do seznamu
    if ($updates && !empty($updates->response)) {
        foreach ($plugins as $name => $plugin) {
            $plugin_list["plugins"][$i]["id"] = $name;
            $plugin_list["plugins"][$i]["name"] = $plugin["Name"];
            $plugin_list["plugins"][$i]["current_version"] = $plugin["Version"];

            if (isset($updates->response[$name])) {
                $plugin_list["plugins"][$i]["update_available"] = "yes";
                $plugin_list["plugins"][$i]["version"] = $updates->response[$name]->new_version;
            } else {
                $plugin_list["plugins"][$i]["update_available"] = "no";
            }
            $i++;
        }
    }

    // Vytvoření výstupu na stránce v adminu
    $revision = isset($plugin_list['revision']) ? $plugin_list['revision'] : 'nothing to update';
    $string = sprintf("Revision: %s\n\n", $revision);

    if (isset($plugin_list['plugins']) && is_array($plugin_list['plugins'])) {
        foreach ($plugin_list['plugins'] as $item) {
            if ('yes' == $item['update_available']) {
                $format = "\n%s\n%s -> %s\n----------------------------------";
                $string .= sprintf($format, $item['name'], $item['current_version'], $item['version']);
            }
        }
    }

    // Výpis do stránky
    echo $string;
}
