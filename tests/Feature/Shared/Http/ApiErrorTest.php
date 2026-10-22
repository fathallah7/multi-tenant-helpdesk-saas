<?php

test('unknown API route returns consistent JSON error', function () {
    $response = $this->getJson('/api/v1/unknown-route');

    $response
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'data', 'errors']);
});
