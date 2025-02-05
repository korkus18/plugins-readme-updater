<?php
/**
 * Plugin Name: Plugins-readme-updater
 * Description: Exportuje informace o nainstalovaných pluginech do .md.
 * Version: 1.0.0
 * Author: Argo22 by Jakub Korous
 */
date_default_timezone_set(get_option('timezone_string') ?: 'Europe/Prague');


// Funkce pro získání informací o pluginech a uložení do souboru .md
function export_plugins_info_to_markdown($environment = 'production') {
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

    // Zpráva o úspěchu
    return $file_path;
}

// Přidání admin menu pro ruční spuštění exportu
add_action('admin_menu', function () {
    add_menu_page(
        'Plugin Info Exporter',
        'Export Plugins Info',
        'manage_options',
        'export-plugins-info',
        'render_export_plugins_page',
        'dashicons-download',
        100
    );
});

// Funkce pro export a commit na GitHub
function export_and_commit_to_github($environment = 'production') {
    // Krok 1: Vytvoření souboru
    $file_path = export_plugins_info_to_markdown($environment);

    // Krok 2: Nahrání na GitHub
    $repo = 'pg'; // Název repozitáře
    $branch = 'main'; // Větev
    $token = ''; // Váš GitHub token
    $username = 'korkus18'; // GitHub uživatelské jméno

    $upload_response = upload_to_github_with_filepath($file_path, $repo, $branch, $token, $username);

    // Vytvoření odpovědí pro úspěch/selhání
    if ($upload_response) {
        return '<div class="updated"><p>Soubor byl úspěšně exportován a nahrán na GitHub: <code>' . esc_html($file_path) . '</code></p></div>';
    } else {
        return '<div class="error"><p>Došlo k chybě při nahrávání souboru na GitHub.</p></div>';
    }
}

// Funkce pro vykreslení stránky
function render_export_plugins_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Nastavení výchozího prostředí
    $environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : 'production';

    echo '<div class="wrap">';
    echo '<h1>Export Plugins Info</h1>';

    // Formulář pro export
    echo '<form method="post">';
    echo '<label for="environment">Prostředí:</label>';
    echo '<select name="environment" id="environment" style="margin-bottom: 20px; display: flex">';
    echo '<option value="production"' . selected($environment, 'production', false) . '>Production</option>';
    echo '<option value="staging"' . selected($environment, 'staging', false) . '>Staging</option>';
    echo '</select>';
    echo '<input type="hidden" name="export_plugins_info" value="1">';
    echo '<button type="submit" class="button button-primary">Exportovat Informace o Pluginech</button>';
    echo '</form>';

    // Zpracování exportu a commitování na GitHub
    if (isset($_POST['export_plugins_info'])) {
        $response = export_and_commit_to_github($environment);
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
    $data = [
        'message' => 'Automated update via API',
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

    if ($upload_http_code === 201 || $upload_http_code === 200) {
        // Úspěch - stylizovaná zpráva pro úspěšné nahrání
        return '<div class="updated"><p>Úspěšně nahráno na GitHub</p></div>';
    } else {
        // Neúspěch - stylizovaná zpráva pro neúspěšné nahrání
        return '<div class="error"><p>Neúspěšně nahráno na GitHub</p></div>';
    }

    return $upload_response;
}


// Parametry
$environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : 'production';
$file_path = wp_upload_dir()['basedir'] . '/' . $environment . '-plugins-readme.md';
$repo = 'pg'; // Název repository (použijte správný název repozitáře bez https://)
$branch = 'main'; // Větev
$token = ''; // Váš GitHub token
$username = 'korkus18'; // GitHub uživatelské jméno

// Spuštění funkce pro nahrání
$response = upload_to_github_with_filepath($file_path, $repo, $branch, $token, $username); // Ujistěte se, že voláte správnou funkci
echo $response;


