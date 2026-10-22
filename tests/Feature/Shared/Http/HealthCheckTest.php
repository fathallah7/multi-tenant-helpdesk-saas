<?php

test('health endpoint returns success', function () {
    $response = $this->getJson('/api/v1/health');

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'healthy')
        ->assertJsonPath('errors', null);
});
