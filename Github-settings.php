<?php
// Zabrání přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

// Funkce pro přidání nebo přepsání konstanty ve wp-config.php
function update_constant_in_wpconfig($constant_name, $constant_value) {
    $config_file = ABSPATH . 'wp-config.php';

    if (!is_writable($config_file)) {
        return false;
    }

    $config_content = file_get_contents($config_file);

    // Pokud konstanta už existuje, přepíše ji
    if (strpos($config_content, "define('$constant_name',") !== false) {
        $config_content = preg_replace(
            "/define\('$constant_name', '.*?'\);/",
            "define('$constant_name', '$constant_value');",
            $config_content
        );
    } else {
        // Pokud neexistuje, přidá ji na konec před "That's all, stop editing!"
        $config_content = str_replace(
            "/* That's all, stop editing! */",
            "define('$constant_name', '$constant_value');\n/* That's all, stop editing! */",
            $config_content
        );
    }

    file_put_contents($config_file, $config_content);
    return true;
}

//  Přidáváme podmínku, aby se změny prováděly pouze při odeslání správného formuláře
if (isset($_POST['github_settings_form'])) { // Opraveno pro správné rozpoznání formuláře
    update_option('github_repo', sanitize_text_field($_POST['github_repo']));
    update_option('github_branch', sanitize_text_field($_POST['github_branch']));
    update_option('github_token', sanitize_text_field($_POST['github_token']));
    update_option('github_username', sanitize_text_field($_POST['github_username']));

    wp_cache_delete('github_repo', 'options'); // Odstranění cache pro danou hodnotu
    wp_cache_flush(); // Celkové vyčištění cache

    if (isset($_POST['github_settings_form'])) {
        $repo = sanitize_text_field($_POST['github_repo']);
        $branch = sanitize_text_field($_POST['github_branch']);
        $token = sanitize_text_field($_POST['github_token']);
        $username = sanitize_text_field($_POST['github_username']);

        update_option('github_repo', $repo);
        update_option('github_branch', $branch);
        update_option('github_token', $token);
        update_option('github_username', $username);

        wp_cache_delete('github_repo', 'options');
        wp_cache_flush();

        echo '<div class="updated"><p>Nastavení bylo úspěšně uloženo:</p>';
        echo '<ul>';
        echo '<li><strong>GitHub Repo:</strong> ' . esc_html($repo) . '</li>';
        echo '<li><strong>GitHub Branch:</strong> ' . esc_html($branch) . '</li>';
        echo '<li><strong>GitHub Token:</strong> ' . esc_html(substr($token, 0, 5)) . '*****</li>'; // Částečné skrytí tokenu
        echo '<li><strong>GitHub Username:</strong> ' . esc_html($username) . '</li>';
        echo '</ul>';
        echo '</div>';
    }

}


// Funkce pro vykreslení administrační stránky
function render_admin_settings_page() {
    $current_repo = get_option('github_repo', '');
    $current_branch = get_option('github_branch', '');
    $current_token = get_option('github_token', '');
    $current_username = get_option('github_username', '');

    // Pokud už token existuje, zobrazíme jen hvězdičky
    $masked_token = !empty($current_token) ? str_repeat('*', 10) : '';

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
                        <input type="text" id="github_token" name="github_token" value="<?php echo esc_attr($masked_token); ?>"
                               onfocus="clearMaskedToken()" required>
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

    <script>
        function clearMaskedToken() {
            var tokenField = document.getElementById("github_token");
            if (tokenField.value === "**********") {
                tokenField.value = ""; // Po kliknutí do pole odstraníme hvězdičky
            }
        }
    </script>
    <?php
}

