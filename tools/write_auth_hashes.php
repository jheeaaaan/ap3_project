<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$input = stream_get_contents(STDIN);
$passwords = preg_split('/\r\n|\r|\n/', rtrim((string) $input, "\r\n"));
if (count($passwords) !== 2 || strlen($passwords[0]) < 12 || strlen($passwords[1]) < 12
    || hash_equals($passwords[0], $passwords[1])) {
    fwrite(STDERR, "Two distinct passwords of at least 12 characters are required.\n");
    exit(2);
}

$values = [
    'AUTH_ADMIN_PASSWORD_HASH' => base64_encode(password_hash($passwords[0], PASSWORD_DEFAULT)),
    'AUTH_STAFF_PASSWORD_HASH' => base64_encode(password_hash($passwords[1], PASSWORD_DEFAULT)),
];
unset($passwords, $input);

foreach ($values as $key => $value) {
    fwrite(STDOUT, $key . '=' . $value . PHP_EOL);
}
