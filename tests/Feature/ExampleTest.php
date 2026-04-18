<?php

test('the application redirects to releases dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('admin.releases.index'));
});
