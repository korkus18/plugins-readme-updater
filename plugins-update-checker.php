<?php
if (!function_exists('wp_get_current_user')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

// Nastavení časového pásma podle WordPressu
date_default_timezone_set(get_option('timezone_string') ?: 'Europe/Prague');

// Zabránění přímému přístupu k souboru
if (!defined('ABSPATH')) {
    exit;
}

// Funkce pro vykreslení administrační stránky
function render_plugins_update_checker_page() {
    ?>
    <div class="wrap">
        <h2>Plugins Update Checker</h2>
        <pre><?php plugins_update_checker(); ?></pre>
    </div>
    <?php
}

// Funkce pro kontrolu aktualizací pluginů, šablon a WordPressu
function plugins_update_checker() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // ❗ Resetujeme cache pro všechny aktualizace
    delete_site_transient('update_plugins');
    delete_site_transient('update_themes');
    delete_site_transient('update_core');

    // ❗ Vynutíme kontrolu aktualizací
    wp_update_plugins();
    wp_update_themes();
    wp_version_check();

    // ❗ Získáme aktualizace
    $updates_plugins = get_site_transient('update_plugins');
    $updates_themes = get_site_transient('update_themes');
    $updates_core = get_site_transient('update_core');

    $plugins = get_plugins();
    $themes = wp_get_themes();
    $core_version = get_bloginfo('version');

    $plugin_list = [];
    $theme_list = [];
    $core_update_available = false;
    $i = 1;

    // ✅ Kontrola dostupnosti aktualizací WordPressu
    if ($updates_core && isset($updates_core->updates) && !empty($updates_core->updates)) {
        foreach ($updates_core->updates as $update) {
            if (isset($update->version) && version_compare($core_version, $update->version, '<')) {
                $core_update_available = "WordPress Core (aktuální: $core_version → nová: $update->version)";
            }
        }
    }

    // ✅ Kontrola dostupnosti aktualizací pluginů
    if ($updates_plugins && !empty($updates_plugins->response)) {
        foreach ($plugins as $name => $plugin) {
            if (isset($updates_plugins->response[$name])) {
                $plugin_list[] = "* " . $plugin["Name"] . " (aktuální: " . $plugin["Version"] . " → nová: " . $updates_plugins->response[$name]->new_version . ")";
            }
        }
    }

    // ✅ Kontrola dostupnosti aktualizací šablon
    if ($updates_themes && !empty($updates_themes->response)) {
        foreach ($themes as $slug => $theme) {
            if (isset($updates_themes->response[$slug])) {
                $theme_list[] = "* " . $theme->get('Name') . " (aktuální: " . $theme->get('Version') . " → nová: " . $updates_themes->response[$slug]['new_version'] . ")";
            }
        }
    }

    // ✅ Sestavení výpisu do stránky
    echo "🔍 **WordPress Update Report**\n\n";

    if ($core_update_available) {
        echo "⚠️ **Dostupná aktualizace WordPress Core:**\n$core_update_available\n\n";
    } else {
        echo "✅ WordPress je aktuální.\n\n";
    }

    if (!empty($plugin_list)) {
        echo "🔧 **Pluginy s dostupnými aktualizacemi:**\n" . implode("\n", $plugin_list) . "\n\n";
    } else {
        echo "✅ Všechny pluginy jsou aktuální.\n\n";
    }

    if (!empty($theme_list)) {
        echo "🎨 **Šablony s dostupnými aktualizacemi:**\n" . implode("\n", $theme_list) . "\n\n";
    } else {
        echo "✅ Všechny šablony jsou aktuální.\n";
    }
}

// ❗ Funkce pro získání seznamu aktualizací (export pro Slack)
function get_plugins_update_report() {
    if (!function_exists('wp_get_current_user')) {
        require_once ABSPATH . 'wp-includes/pluggable.php';
    }

    if (!current_user_can('manage_options')) {
        return 'Nemáš oprávnění pro zobrazení tohoto reportu.';
    }

    // ❗ Resetujeme cache pro všechny aktualizace
    delete_site_transient('update_plugins');
    delete_site_transient('update_themes');
    delete_site_transient('update_core');

    // ❗ Vynutíme kontrolu aktualizací
    wp_update_plugins();
    wp_update_themes();
    wp_version_check();

    // ❗ Získáme aktualizace
    $updates_plugins = get_site_transient('update_plugins');
    $updates_themes = get_site_transient('update_themes');
    $updates_core = get_site_transient('update_core');

    $plugins = get_plugins();
    $themes = wp_get_themes();
    $core_version = get_bloginfo('version');

    $plugin_list = [];
    $theme_list = [];
    $core_update_available = false;

    // ✅ Kontrola dostupnosti aktualizací WordPressu
    if ($updates_core && isset($updates_core->updates) && !empty($updates_core->updates)) {
        foreach ($updates_core->updates as $update) {
            if (isset($update->version) && version_compare($core_version, $update->version, '<')) {
                $core_update_available = "WordPress Core (aktuální: $core_version → nová: $update->version)";
            }
        }
    }

    // ✅ Kontrola dostupnosti aktualizací pluginů
    if ($updates_plugins && !empty($updates_plugins->response)) {
        foreach ($plugins as $name => $plugin) {
            if (isset($updates_plugins->response[$name])) {
                $plugin_list[] = "* " . $plugin["Name"] . " (aktuální: " . $plugin["Version"] . " → nová: " . $updates_plugins->response[$name]->new_version . ")";
            }
        }
    }

    // ✅ Kontrola dostupnosti aktualizací šablon
    if ($updates_themes && !empty($updates_themes->response)) {
        foreach ($themes as $slug => $theme) {
            if (isset($updates_themes->response[$slug])) {
                $theme_list[] = "* " . $theme->get('Name') . " (aktuální: " . $theme->get('Version') . " → nová: " . $updates_themes->response[$slug]['new_version'] . ")";
            }
        }
    }

    // ✅ Sestavení zprávy pro Slack
    $report = "*WordPress Update Report*\n\n";

    if ($core_update_available) {
        $report .= "⚠️ *Dostupná aktualizace WordPress Core:*\n$core_update_available\n\n";
    } else {
        $report .= "✅ WordPress je aktuální.\n\n";
    }

    if (!empty($plugin_list)) {
        $report .= "🔧 *Pluginy s dostupnými aktualizacemi:*\n" . implode("\n", $plugin_list) . "\n\n";
    } else {
        $report .= "✅ Všechny pluginy jsou aktuální.\n\n";
    }

    if (!empty($theme_list)) {
        $report .= "🎨 *Šablony s dostupnými aktualizacemi:*\n" . implode("\n", $theme_list) . "\n\n";
    } else {
        $report .= "✅ Všechny šablony jsou aktuální.\n";
    }

    return $report;
}
