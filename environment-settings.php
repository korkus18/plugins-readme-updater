<?php
// Zabránění přímému přístupu k souboru
if (!defined('ABSPATH')) {
    exit;
}

// Funkce pro vykreslení stránky nastavení environmentu
function render_environment_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Načtení uložené hodnoty nebo výchozího 'production'
    $environment = get_option('export_environment', 'production');

    // Pokud uživatel odešle formulář, uložíme novou hodnotu
    if (isset($_POST['save_environment_settings'])) {
        $environment = sanitize_text_field($_POST['environment']);
        update_option('export_environment', $environment); // Uloží do databáze
        echo '<div class="updated"><p>Nastavení bylo uloženo.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Nastavení prostředí</h1>';

    // Formulář pro uložení nastavení
    echo '<form method="post">';
    echo '<label for="environment">Vyberte prostředí:</label>';
    echo '<select name="environment" id="environment" style="margin-bottom: 20px; display: flex">';
    echo '<option value="production"' . selected($environment, 'production', false) . '>Production</option>';
    echo '<option value="staging"' . selected($environment, 'staging', false) . '>Staging</option>';
    echo '</select>';
    echo '<input type="hidden" name="save_environment_settings" value="1">';
    echo '<button type="submit" class="button button-primary">Uložit Nastavení</button>';
    echo '</form>';

    echo '</div>';
}
