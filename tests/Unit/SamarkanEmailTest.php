<?php

use App\Support\KodeVerifikasi;

test('masked email keeps the first two and last two characters of the name visible', function (string $email, string $samaran) {
    expect(KodeVerifikasi::samarkan($email))->toBe($samaran);
})->with([
    ['budisantoso@gmail.com', 'bu*******so@gmail.com'],
    ['andi5@example.com', 'an*i5@example.com'],
    // Nama 4 huruf atau kurang akan terbuka seluruhnya, jadi hanya huruf pertama yang tampil.
    ['budi@gmail.com', 'b***@gmail.com'],
]);
