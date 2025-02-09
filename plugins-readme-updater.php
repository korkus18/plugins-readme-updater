<?php
/**
 * Plugin Name: Plugins-readme-updater
 * Description: Exportuje informace o nainstalovaných pluginech do .md.
 * Version: 1.0.0
 * Author: Argo22 by Jakub Korous
 */
date_default_timezone_set(get_option('timezone_string') ?: 'Europe/Prague');
require_once plugin_dir_path(__FILE__) . 'github-settings.php';
require_once plugin_dir_path(__FILE__) . 'environment-settings.php';
require_once plugin_dir_path(__FILE__) . 'plugins-update-checker.php';
require_once plugin_dir_path(__FILE__) . 'slack-settings.php';





// Funkce pro získání informací o pluginech a uložení do souboru .md
function export_plugins_info_to_markdown($environment = '') {
    if (!$environment) {
        $environment = get_option('export_environment', 'production'); // Použití uloženého prostředí
    }

    // Získání všech nainstalovaných pluginů
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = get_plugins();
    $site_title = get_bloginfo('name');
    $site_address = get_bloginfo('url');

    // Záhlaví
    $markdown_content = "### Site Title: " . $site_title . "\n\n";
    $markdown_content .= "### Site Address: " . $site_address . "\n\n";
    $markdown_content .= "### Environment: " . ucfirst($environment) . "\n\n";
    $markdown_content .= "### Date: " . date("Y-m-d H:i") . "\n\n";

    // Získání informací o WordPressu
    $markdown_content .= "### Wordpress Core, Version " . get_bloginfo('version') . "\n\n";

    // Získání informací o šabloně
    $theme = wp_get_theme();
    if ($theme->parent()) {
        $markdown_content .= "### Theme (Child): " . $theme->get('Name') . ", Version: " . $theme->get('Version') . "\n\n";
    } else {
        $markdown_content .= "### Theme (Parent): " . $theme->get('Name') . ", Version: " . $theme->get('Version') . "\n\n";
    }

    // Získání informací o pluginech
    $markdown_content .= "### Plugins:\n\n";

    foreach ($plugins as $plugin_path => $plugin_info) {
        $is_active = is_plugin_active($plugin_path);
        $plugin_url = !empty($plugin_info['PluginURI']) ? $plugin_info['PluginURI'] : '';

        $markdown_content .= "#### " . (!empty($plugin_url) ? "[" . $plugin_info['Name'] . "](" . $plugin_url . ")" : $plugin_info['Name']) . "\n";
        $markdown_content .= "Version " . $plugin_info['Version'] . "\n";
        $markdown_content .= $plugin_info['Description'] . "\n";
        $markdown_content .= "Autor: " . $plugin_info['Author'] . "\n";
        $markdown_content .= ($is_active ? "active" : "inactive") . "\n\n";
    }

    // Definice cesty k souboru
    $upload_dir = wp_upload_dir();
    $file_path = trailingslashit($upload_dir['basedir']) . $environment . '-plugins-readme.md';

    // Uložení dat do souboru
    file_put_contents($file_path, $markdown_content);

    return $file_path;
}

// Přidání admin menu pro ruční spuštění exportu
add_action('admin_menu', function () {
    // Hlavní položka v admin menu
    add_menu_page(
        'Plugins Readme Updater', // Titulek stránky
        'Plugins Readme Updater', // Název v menu
        'manage_options', // Potřebné oprávnění
        'plugins-readme-updater', // Slug stránky
        'render_export_plugins_page', // Callback pro obsah stránky
        'dashicons-admin-generic', // Ikona
        100 // Pozice v menu
    );
    add_submenu_page(
        'plugins-readme-updater', // Slug hlavní stránky
        'Plugins Update Checker', // Titulek stránky
        'Plugins to update', // Název v menu
        'manage_options', // Oprávnění
        'plugins-update-checker', // Slug submenu
        'render_plugins_update_checker_page' // Callback funkce pro vykreslení obsahu stránky
    );
    // Submenu pro admin settings
    add_submenu_page(
        'plugins-readme-updater', // Hlavní stránka, pod kterou submenu patří
        'Github Settings', // Titulek stránky
        'Github Settings', // Název v submenu
        'manage_options', // Potřebné oprávnění
        'Github-settings', // Slug submenu
        'render_admin_settings_page' // Callback pro obsah stránky
    );

    add_submenu_page(
        'plugins-readme-updater', // Hlavní stránka, pod kterou submenu patří
        'Environment settings', // Titulek stránky
        'Environment settings', // Název v menu
        'manage_options', // Potřebná oprávnění
        'environment-settings', // Slug stránky
        'render_environment_settings_page' // Callback pro obsah stránky
    );

    add_submenu_page(
        'plugins-readme-updater', // Nadřazená stránka (hlavní menu pluginu)
        'Slack Settings', // Titulek stránky
        'Slack Settings', // Název v menu
        'manage_options', // Oprávnění
        'slack-settings', // Slug submenu
        'render_slack_settings_page' // Callback funkce pro zobrazení stránky
    );
});

function load_admin_settings_page() {
    require_once plugin_dir_path(__FILE__) . 'Github-settings.php';
    render_admin_settings_page();
}

// Funkce pro export a commit na GitHub
function export_and_commit_to_github($environment = '') {
    if (empty($environment)) {
        $environment = get_option('export_environment', 'production'); // Použití uloženého prostředí
    }


    $file_path = export_plugins_info_to_markdown($environment);

    $repo = get_option('github_repo', '');
    $branch = get_option('github_branch', '');
    $token = get_option('github_token', '');
    $username = get_option('github_username', '');

    $upload_response = upload_to_github_with_filepath($file_path, $repo, $branch, $token, $username);

    if ($upload_response) {
        $github_url = "https://github.com/$username/$repo/blob/$branch/" . basename($file_path);

        return '<div class="updated">
            <p>Soubor byl úspěšně nahrán na GitHub:
            <a href="' . esc_url($github_url) . '" target="_blank">' . esc_html($github_url) . '</a></p>
            <p>A uložen do souboru: <code>' . esc_html($file_path) . '</code></p>
        </div>';
    } else {
        return '<div class="error"><p>Došlo k chybě při nahrávání souboru na GitHub.</p></div>';
    }

}



// Funkce pro vykreslení stránky
function render_export_plugins_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Načtení uložené hodnoty nebo výchozího 'production'
    $environment = get_option('export_environment', 'production');

    // Pokud uživatel odešle formulář, uložíme novou hodnotu
    if (isset($_POST['export_plugins_info'])) {
        $environment = get_option('export_environment', 'production'); // Použití uloženého prostředí
        $response = export_and_commit_to_github($environment);
    }


    echo '<div class="wrap">';
    echo '<h1>SLA plugin</h1>';

    echo "<h3>Nahrát výpis na slack</h3>";
    echo '<form method="post">';
    echo '<input type="hidden" name="export_to_slack" value="1">';
    echo '<button type="submit" class="button button-primary">Slack</button>';
    echo '</form>';

    echo "<h3>commitnout plugin-readme.md</h3>";
    echo '<form method="post">';
    echo '<input type="hidden" name="export_plugins_info" value="1">';
    echo '<button type="submit" class="button button-primary">GitHub</button>';
    echo '</form>';

    // Výpis odpovědi po exportu
    if (isset($response)) {
        echo $response;
    }

    echo '</div>';
}











function upload_to_github_with_filepath($file_path, $repo, $branch, $token, $username) {
    $content = file_get_contents($file_path);
    $base64_content = base64_encode($content);
    $filename = basename($file_path);

    // API URL pro soubor v kořenové složce repozitáře
    $url = "https://api.github.com/repos/$username/$repo/contents/$filename";

    // 1. Získání SHA souboru
    $headers = [
        "Authorization: token $token",
        "User-Agent: MyApp",
        "Accept: application/vnd.github.v3+json",
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $sha = null;
    if ($http_code === 200) { // Soubor existuje
        $response_data = json_decode($response, true);
        $sha = $response_data['sha'] ?? null;
    } else {
        echo "Error: File does not exist or cannot retrieve SHA. Creating new file instead.\n";
    }

    // 2. Kontrola, zda se obsah změnil
    // Pokud soubor již existuje, porovnáme obsah
    if ($sha) {
        $existing_content = base64_decode($response_data['content']);
        if ($existing_content === $content) {
            // Pokud se obsah nezměnil, neprovádíme commit
            return; // Konec funkce, žádný commit se neprovede
        }
    }

    // 3. Nahrání obsahu (aktualizace nebo vytvoření)
    $environment = get_option('export_environment', 'production'); // Získání aktuálního prostředí
    $data = [
        'message' => 'update-plugins-readme.md-' . date('Y-m-d-H-i') . '-' . $environment,
        'content' => $base64_content,
        'branch' => $branch,
    ];
    if ($sha) {
        $data['sha'] = $sha; // Přidání SHA pouze pokud soubor existuje
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $upload_response = curl_exec($ch);
    $upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Upload response: " . $upload_response);

    return $upload_response;
}



// Parametry
$environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : 'production';
$file_path = wp_upload_dir()['basedir'] . '/' . $environment . '-plugins-readme.md';
$repo = get_option('github_repo', '');
$branch = get_option('github_branch', '');
$token = get_option('github_token', '');
$username = get_option('github_username', '');


// Spuštění funkce pro nahrání
$response = upload_to_github_with_filepath($file_path, $repo, $branch, $token, $username); // Ujistěte se, že voláte správnou funkci
echo $response;




function send_export_to_slack($message) {
    $slack_webhook_url = get_option('slack_webhook_url', ''); // Slack webhook URL uložená v databázi

    if (empty($slack_webhook_url)) {
        return '<div class="error"><p>Chyba: Slack webhook URL není nastavena.</p></div>';
    }

    $payload = json_encode(['text' => $message]);

    $args = [
        'body'        => $payload,
        'headers'     => ['Content-Type' => 'application/json'],
        'timeout'     => 30,
    ];

    $response = wp_remote_post($slack_webhook_url, $args);

    if (is_wp_error($response)) {
        return '<div class="error"><p>Chyba při odesílání na Slack: ' . $response->get_error_message() . '</p></div>';
    } else {
        return '<div class="updated"><p>Export úspěšně odeslán na Slack.</p></div>';
    }
}

if (isset($_POST['export_to_slack'])) {
    $message = get_plugins_update_report(); // Načteme správný výpis pluginů
    $response = send_export_to_slack($message); // Pošleme report na Slack
    echo $response;
}

