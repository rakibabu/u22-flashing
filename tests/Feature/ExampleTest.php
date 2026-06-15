<?php

test('guest wordt vanaf home naar login gestuurd', function () {
    $this->get(route('home'))
        ->assertRedirect(route('login'));
});
