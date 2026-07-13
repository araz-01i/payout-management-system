<?php

test('returns a redirect to users index', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/users');
});
