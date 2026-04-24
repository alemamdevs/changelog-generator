<?php

test('the application redirects to projects dashboard', function (): void {
    $response = $this->get('/');

    $response->assertRedirect(route('admin.projects.index'));
});
