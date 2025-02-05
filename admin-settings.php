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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['github_settings_form'])) {
    if (isset($_POST['github_repo'])) {
        update_constant_in_wpconfig('GITHUB_REPO', sanitize_text_field($_POST['github_repo']));
    }
    if (isset($_POST['github_branch'])) {
        update_constant_in_wpconfig('GITHUB_BRANCH', sanitize_text_field($_POST['github_branch']));
    }
    if (isset($_POST['github_token'])) {
        update_constant_in_wpconfig('GITHUB_TOKEN', sanitize_text_field($_POST['github_token']));
    }
    if (isset($_POST['github_username'])) {
        update_constant_in_wpconfig('GITHUB_USERNAME', sanitize_text_field($_POST['github_username']));
    }

    echo '<div class="updated"><p>Nastavení bylo úspěšně uloženo do wp-config.php.</p></div>';
}


// Funkce pro vykreslení administrační stránky
function render_admin_settings_page() {
    ?>
    <div class="wrap">
        <h2>Nastavení GitHub Připojení</h2>
        <form method="post">
            <!-- Skrytý input pro rozpoznání formuláře -->
            <input type="hidden" name="github_settings_form" value="1">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="github_repo">GitHub Repo:</label></th>
                    <td><input type="text" id="github_repo" name="github_repo" value="" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="github_branch">GitHub Branch:</label></th>
                    <td><input type="text" id="github_branch" name="github_branch" value="" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="github_token">GitHub Token:</label></th>
                    <td><input type="text" id="github_token" name="github_token" value="" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="github_username">GitHub Username:</label></th>
                    <td><input type="text" id="github_username" name="github_username" value="" required></td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">Uložit</button>
        </form>
    </div>
    <?php
}
