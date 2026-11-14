<?php
/** Return a validated sort key, direction, and SQL ordering from fixed columns. */
function listing_sort_state(array $columns, string $default): array
{
    $requestedSort = $_GET['sort'] ?? $default;
    $sort = is_string($requestedSort) && isset($columns[$requestedSort])
        ? $requestedSort
        : $default;

    $requestedDirection = strtolower((string) ($_GET['direction'] ?? 'desc'));
    $direction = in_array($requestedDirection, ['asc', 'desc'], true)
        ? $requestedDirection
        : 'desc';

    return [$sort, $direction, $columns[$sort] . ' ' . strtoupper($direction)];
}

