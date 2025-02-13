<?php
/**
 * Plugin Name: SLA plugin
 * Description: Automates README updates for plugins, ensuring consistency and Slack integration for notifications.
 * Version: 1.0.0
 * Author: Argo22 by Jakub Korous
 */

// Zabránění přímému přístupu k souboru
if (!defined('ABSPATH')) {
    exit;
}

// Funkce pro přidání nebo přepsání GitHub tokenu ve wp-config.php
function update_github_token_in_wpconfig($token) {
    $config_file = ABSPATH . 'wp-config.php';

    if (!is_writable($config_file)) {
        return false; // Nelze zapisovat do souboru
    }

    $config_content = file_get_contents($config_file);

    // Pokud už konstanta existuje, přepíšeme ji
    if (strpos($config_content, "define('GITHUB_TOKEN',") !== false) {
        $config_content = preg_replace(
            "/define\('GITHUB_TOKEN', '.*?'\);/",
            "define('GITHUB_TOKEN', '" . addslashes($token) . "');",
            $config_content
        );
    } else {
        // Pokud neexistuje, přidáme ji před "That's all, stop editing!"
        $config_content = str_replace(
            "/* That's all, stop editing! */",
            "define('GITHUB_TOKEN', '" . addslashes($token) . "');\n/* That's all, stop editing! */",
            $config_content
        );
    }

    return file_put_contents($config_file, $config_content) !== false;
}

// Zpracování formuláře
if (isset($_POST['github_settings_form'])) {
    $repo = sanitize_text_field($_POST['github_repo']);
    $branch = sanitize_text_field($_POST['github_branch']);
    $username = sanitize_text_field($_POST['github_username']);
    $new_token = sanitize_text_field($_POST['github_token']);

    update_option('github_repo', $repo);
    update_option('github_branch', $branch);
    update_option('github_username', $username);

    // Pokud je nový token zadán, zapíšeme ho do wp-config.php
   if (isset($_POST['github_settings_form'])) {
       $repo = sanitize_text_field($_POST['github_repo']);
       $branch = sanitize_text_field($_POST['github_branch']);
       $username = sanitize_text_field($_POST['github_username']);
       $new_token = sanitize_text_field($_POST['github_token']);

       update_option('github_repo', $repo);
       update_option('github_branch', $branch);
       update_option('github_username', $username);

       // Token se uloží pouze pokud uživatel zadal nový
       if (!empty($new_token)) {
           update_option('github_token', $new_token);
       }

       wp_cache_delete('github_repo', 'options');
       wp_cache_flush();

       // Výpis zprávy s uloženými hodnotami (bez zobrazení tokenu!)
       echo '<div class="updated"><p>Nastavení bylo úspěšně uloženo:</p>';
       echo '<ul>';
       echo '<li><strong>GitHub Repo:</strong> ' . esc_html($repo) . '</li>';
       echo '<li><strong>GitHub Branch:</strong> ' . esc_html($branch) . '</li>';
       echo '<li><strong>GitHub Username:</strong> ' . esc_html($username) . '</li>';
       echo '</ul>';
       echo '<p>Pokud jste změnili token, byl aktualizován.</p>';
       echo '</div>';
   }

    wp_cache_flush();
}

// Funkce pro vykreslení administrační stránky
function render_admin_settings_page() {
    $current_repo = get_option('github_repo', '');
    $current_branch = get_option('github_branch', '');
    $current_username = get_option('github_username', '');

    ?>
    <div class="wrap">
        <h2>Nastavení GitHub Připojení</h2>

        <form method="post">
            <input type="hidden" name="github_settings_form" value="1">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="github_repo">GitHub Repo:</label></th>
                    <td><input type="text" id="github_repo" name="github_repo" value="<?php echo esc_attr($current_repo); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="github_branch">GitHub Branch:</label></th>
                    <td><input type="text" id="github_branch" name="github_branch" value="<?php echo esc_attr($current_branch); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="github_token">GitHub Token:</label></th>
                    <td>
                        <input class="large-text" type="password" id="github_token" name="github_token" placeholder="Nový token (ponechte prázdné, pokud nechcete měnit)">
                        <p><small>Váš aktuální token není z bezpečnostních důvodů zobrazen.</small></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="github_username">GitHub Username:</label></th>
                    <td><input type="text" id="github_username" name="github_username" value="<?php echo esc_attr($current_username); ?>" required></td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">Uložit</button>
        </form>
    </div>
<style>
    .large-text {
        width: 100%; /* Roztáhne input na celou šířku */
        max-width: 400px; /* Omezí maximální šířku */
    }
</style>
    <?php
}
