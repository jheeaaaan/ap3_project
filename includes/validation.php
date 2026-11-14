<?php
// Confirm a form date is an actual calendar date in YYYY-MM-DD format.
function is_valid_iso_date($value)
{
    if (!is_string($value)) {
        return false;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    $errors = DateTime::getLastErrors();
    return $date !== false
        && $date->format('Y-m-d') === $value
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

// Check that a submitted ID is one of the options loaded from the database.
function lookup_option_exists(array $options, $submitted_id, $id_column)
{
    if (!is_scalar($submitted_id) || !ctype_digit((string) $submitted_id)) {
        return false;
    }

    foreach ($options as $option) {
        if (isset($option[$id_column]) && (string) $option[$id_column] === (string) $submitted_id) {
            return true;
        }
    }

    return false;
}
