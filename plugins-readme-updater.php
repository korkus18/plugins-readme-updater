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
        'SLA Argo22', // Titulek stránky
        'SLA Argo22', // Název v menu
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
    add_submenu_page(
        'plugins-readme-updater', // Hlavní stránka, pod kterou submenu patří
        'Github Settings', // Titulek stránky
        'Github Settings', // Název v submenu
        'manage_options', // Potřebné oprávnění
        'Github-settings', // Slug submenu
        'render_admin_settings_page' // Callback pro obsah stránky
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

    $slack_recipient = get_option('slack_recipient', '');

    echo '<div class="wrap" style="max-width: 600px; margin: 0 auto;">';
    echo '<h1 style="font-size: 22px; font-weight: 600; margin-bottom: 20px;">SLA Argo22</h1>';

if ($environment === 'staging') {
    // 🔹 Slack plugins info
    echo '<div style="padding: 15px 0; border-bottom: 1px solid #ddd;">';
    echo '<h2 style="font-size: 18px; font-weight: 500; margin-bottom: 10px;">📢 Slack plugins update info</h2>';
    echo '<form method="post">';
    echo '<label for="slack_recipient" style="display: block; font-size: 14px; margin-bottom: 5px;">Reviewer:</label>';
    echo '<input type="text" id="slack_recipient" name="slack_recipient" value="' . esc_attr($slack_recipient) . '" placeholder="@Petr nebo @team" style="width: 100%; padding: 8px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px;">';
    echo '<input type="hidden" name="save_slack_recipient" value="1">';
    echo '<button type="submit" class="button" style="margin-top: 10px;">Uložit</button>';
    echo '</form>';
    echo '<form method="post">';
    echo '<label for="update_note" style="display: block; font-size: 14px; margin-bottom: 5px;">Poznámka:</label>';
    echo '<textarea id="update_note" name="update_note" placeholder="Doplňující informace..." style="width: 100%; height: 100px; padding: 8px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px;">' . esc_textarea(get_option('update_note', '')) . '</textarea>';
    echo '<input type="hidden" name="save_update_note" value="1">';
    echo '<button type="submit" class="button" style="margin-top: 10px;">Uložit</button>';
    echo '</form>';
    echo '<h2 style="font-size: 18px; font-weight: 500; margin-bottom: 10px;">📤 Odeslat informace o pluginech na Slack</h2>';
    echo '<form method="post" id="slackExportForm">';
    echo '<input type="hidden" name="export_to_slack" value="1">';
    echo '<button type="submit" id="export_to_slack_button" class="button button-primary" style="width: 100%; padding: 10px; font-size: 15px;">Odeslat na Slack</button>';
    echo '</form>';
    echo '</div>';




    // 🔹 Slack kontrola webu
    echo '<div style="padding: 15px 0; border-bottom: 1px solid #ddd;">';
    echo '<h2 style="font-size: 18px; font-weight: 500; margin-bottom: 10px;">💬 Kontrola webu</h2>';
    echo '<form method="post">';
    echo '<label for="slack_thread_message" style="display: block; font-size: 14px; margin-bottom: 5px;">Infromace o kontrole webu:</label>';
    echo '<textarea id="slack_thread_message" name="slack_thread_message" placeholder="Napište zprávu do threadu..." style="width: 100%; height: 160px; padding: 8px; font-size: 14px; border: 1px solid #ccc; border-radius: 5px;">' . esc_textarea(get_option('slack_thread_message', '')) . '</textarea>';
    echo '<input type="hidden" name="save_slack_thread_message" value="1">';
    echo '<button type="submit" class="button" style="margin-top: 10px;">Uložit</button>';
    echo '</form>';
    echo '<h2 style="font-size: 18px; font-weight: 500; margin-bottom: 10px;">📩 Odeslat informace o Kontrole webu</h2>';
    echo '<form method="post" id="slackThreadForm">';
    echo '<input type="hidden" name="send_to_slack_thread" value="1">';
    echo '<button type="submit" id="send_to_slack_thread_button" class="button button-primary" style="width: 100%; padding: 10px; font-size: 15px;">Odeslat do threadu</button>';
    echo '</form>';
    echo '</div>';

}

    // 🔹 GitHub .md update
    echo '<div style="padding: 15px 0;">';
    echo '<h2 style="font-size: 18px; font-weight: 500; margin-bottom: 10px;">🚀 Updatnout ' . esc_html($environment) . '-plugins-readme.md</h2>';
    echo '<form method="post" id="githubExportForm">';
    echo '<input type="hidden" name="export_plugins_info" value="1">';
    echo '<button type="submit" id="export_to_github_button" class="button button-primary" style="width: 100%; padding: 10px; font-size: 15px;">Commitnout na GitHub</button>';
    echo '</form>';
    echo '<div id="githubSuccessMessage" style="display: none; margin-top: 10px; padding: 10px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px;">✅ Commit úspěšně proveden na GitHub!</div>';
    echo '</div>';





    // Výpis odpovědi po exportu
    if (isset($response)) {
        echo $response;
    }

    echo '</div>';
}
// Uložení nové zprávy do threadu po odeslání formuláře
if (isset($_POST['save_slack_thread_message'])) {
    $slack_thread_message = sanitize_textarea_field($_POST['slack_thread_message']);
    update_option('slack_thread_message', $slack_thread_message);
    echo '<div class="updated"><p>Zpráva do threadu byla uložena.</p></div>';
}






// Uložení nové hodnoty poznámky po odeslání formuláře
if (isset($_POST['save_update_note'])) {
    $update_note = sanitize_textarea_field($_POST['update_note']);
    update_option('update_note', $update_note); // Uloží poznámku
    echo '<div class="updated"><p>Poznámka byla úspěšně uložena.</p></div>';
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
    $slack_token = get_option('slack_oauth_token', ''); // OAuth Token
    $slack_channel = get_option('slack_channel_id', ''); // ID kanálu

    if (empty($slack_token) || empty($slack_channel)) {
        return '<div class="error"><p>Chyba: Slack OAuth token nebo channel ID není nastaveno.</p></div>';
    }

    // API URL pro odesílání zprávy
    $url = "https://slack.com/api/chat.postMessage";

    // Data zprávy
    $data = [
        'channel' => $slack_channel,
        'text' => $message
    ];

    $args = [
        'body'    => json_encode($data),
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $slack_token,
        ],
        'timeout' => 30,
    ];

    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return '<div class="error"><p>Chyba při odesílání na Slack: ' . $response->get_error_message() . '</p></div>';
    }

    $response_body = wp_remote_retrieve_body($response);
    $decoded_response = json_decode($response_body, true);

    if (!empty($decoded_response['ok']) && !empty($decoded_response['ts'])) {
        update_option('slack_thread_ts', $decoded_response['ts']); // Uložit thread timestamp
        return '<div class="updated"><p>Zpráva úspěšně odeslána na Slack.</p></div>';
    } else {
        return '<div class="error"><p>Chyba: Nepodařilo se získat `thread_ts`.</p></div>';
    }
}


if (isset($_POST['export_to_slack'])) {
    $message = get_plugins_update_report(); // Načteme správný výpis pluginů
    $response = send_export_to_slack($message); // Pošleme report na Slack
    echo $response;
}
if (isset($_POST['send_to_slack_thread'])) {
    $message = get_option('slack_thread_message', '');
    if (!empty($message)) {
        $response = send_message_to_slack_thread($message);
        echo $response;
    } else {
        echo '<div class="error"><p>Chyba: Zpráva do threadu nemůže být prázdná.</p></div>';
    }
}


function send_message_to_slack_thread($message) {
    $slack_token = get_option('slack_oauth_token', '');
    $slack_channel = get_option('slack_channel_id', '');
    $thread_ts = get_option('slack_thread_ts', ''); // Načíst uložený timestamp zprávy

    if (empty($slack_token) || empty($slack_channel) || empty($thread_ts)) {
        return '<div class="error"><p>Chyba: Nelze odeslat zprávu do threadu. Chybí OAuth token, channel ID nebo hlavní zpráva.</p></div>';
    }

    $url = "https://slack.com/api/chat.postMessage";

    $data = [
        'channel'   => $slack_channel,
        'text'      => $message,
        'thread_ts' => $thread_ts // Odeslat jako odpověď do threadu
    ];

    $args = [
        'body'    => json_encode($data),
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $slack_token,
        ],
        'timeout' => 30,
    ];

    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return '<div class="error"><p>Chyba při odesílání do threadu na Slack: ' . $response->get_error_message() . '</p></div>';
    }

    return '<div class="updated"><p>Zpráva úspěšně odeslána do threadu na Slack.</p></div>';
}

// JavaScript pro změnu textu tlačítek při odeslání
echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        const buttonsConfig = [
            { formId: "slackExportForm", buttonId: "export_to_slack_button", buttonText: "Odesílám..." },
            { formId: "githubExportForm", buttonId: "export_to_github_button", buttonText: "Commituji..." },
            { formId: "slackThreadForm", buttonId: "send_to_slack_thread_button", buttonText: "Odesílám..." }
        ];

        buttonsConfig.forEach(({ formId, buttonId, buttonText }) => {
            let form = document.getElementById(formId);
            let button = document.getElementById(buttonId);

            if (form && button) {
                form.addEventListener("submit", function () {
                    button.disabled = true;
                    button.innerText = buttonText;

                    setTimeout(() => {
                        button.innerText = buttonText.replace("...", "nout");
                        button.disabled = false;
                    }, 2000);
                });
            }
        });
    });
</script>';



