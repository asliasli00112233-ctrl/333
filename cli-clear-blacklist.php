<?php
// CLI helper to delete amo_key_blacklist transient
require 'c:\\xampp\\htdocs\\wp\\wp-load.php';

$deleted = delete_transient('amo_key_blacklist');
if ($deleted) {
    echo "Transient 'amo_key_blacklist' deleted\n";
} else {
    // Could be non-existent; indicate current state
    $val = get_transient('amo_key_blacklist');
    if ($val === false) {
        echo "Transient 'amo_key_blacklist' does not exist (or was already removed)\n";
    } else {
        echo "Transient 'amo_key_blacklist' existed but could not be deleted via delete_transient().\n";
    }
}
