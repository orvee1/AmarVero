<?php

test('storefront pages render the noir contour theme shell', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('storefront-shell', false)
        ->assertSee('storefront-texture', false)
        ->assertSee('storefront-header', false)
        ->assertSee('storefront-hero-media', false)
        ->assertSee('storefront-footer', false)
        ->assertDontSee('min-h-screen bg-zinc-50 text-zinc-950', false);
});

test('storefront theme css is scoped to the user side layout', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.storefront-shell')
        ->toContain('.storefront-texture')
        ->toContain('data:image/svg+xml')
        ->toContain('.storefront-shell a.bg-zinc-950')
        ->toContain('.storefront-hero-media img');
});
