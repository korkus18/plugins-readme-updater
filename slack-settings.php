<?php
/**
 * Plugin Name: SLA plugin
 * Description: Automates README updates for plugins, ensuring consistency and Slack integration for notifications.
 * Version: 1.0.0
 * Author: Argo22 by Jakub Korous
 */

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
        <h2>Slack Settings</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="slack_oauth_token">Slack OAuth Token:</label></th>
                    <td><input type="password" id="slack_oauth_token" name="slack_oauth_token" value="<?php echo esc_attr(get_option('slack_oauth_token', '')); ?>" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="slack_channel_id">Slack Channel ID:</label></th>
                    <td><input type="text" id="slack_channel_id" name="slack_channel_id" value="<?php echo esc_attr(get_option('slack_channel_id', '')); ?>" required></td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">Uložit</button>
        </form>
    </div>
    <?php

    if (isset($_POST['slack_oauth_token'])) {
        update_option('slack_oauth_token', sanitize_text_field($_POST['slack_oauth_token']));
    }

    if (isset($_POST['slack_channel_id'])) {
        update_option('slack_channel_id', sanitize_text_field($_POST['slack_channel_id']));
    }
}
