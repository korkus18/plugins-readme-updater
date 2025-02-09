<?php
// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

// Funkce pro uložení Slack Webhook URL
if (isset($_POST['save_slack_settings'])) {
    update_option('slack_webhook_url', sanitize_text_field($_POST['slack_webhook_url']));
    echo '<div class="updated"><p>Slack Webhook URL bylo uloženo.</p></div>';
}

// Funkce pro vykreslení stránky v admin panelu
function render_slack_settings_page() {
    ?>
    <div class="wrap">
        <h2>Nastavení Slack Webhook</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="slack_webhook_url">Slack Webhook URL:</label></th>
                    <td>
                        <input type="text" id="slack_webhook_url" name="slack_webhook_url" value="<?php echo esc_attr(get_option('slack_webhook_url', '')); ?>" class="large-text">
                    </td>
                </tr>
            </table>
            <button type="submit" name="save_slack_settings" class="button button-primary">Uložit</button>
        </form>
    </div>
    <?php
}
