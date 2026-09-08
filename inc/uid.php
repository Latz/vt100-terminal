<?php
/**
 * Visitor UID system: generates and persists a short hex identifier per visitor.
 */

/**
 * Increments and persists the visitor counter, re-seeding it with a random
 * value if the option is missing (e.g. a race right after `switch_theme`
 * deletes it) instead of silently restarting from 0 and risking a numeric
 * collision with previously issued UIDs.
 * @return int The new counter value.
 */
function vt100_next_uid_counter(): int {
    $counter = get_option('vt100_user_counter');
    $counter = (false === $counter) ? random_int(0x100, 0xfff) : (int) $counter;
    $counter++;
    update_option('vt100_user_counter', $counter, false);

    return $counter;
}

function vt100_get_or_create_uid(): string {
    if (!empty($_COOKIE['vt100_uid']) && is_string($_COOKIE['vt100_uid'])) {
        $uid = preg_replace('/[^a-f0-9]/', '', $_COOKIE['vt100_uid']);
        $uid = substr($uid, 0, 32);
        if ($uid) {
            return $uid;
        }
    }

    return dechex(vt100_next_uid_counter());
}
