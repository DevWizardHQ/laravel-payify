<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    $path = app_path('Payify/Drivers');
    if (File::isDirectory($path)) {
        File::deleteDirectory($path);
    }
});

it('scaffolds a custom driver file with substituted placeholders', function () {
    $this->artisan('payify:make-driver', ['name' => 'Paddle'])
        ->assertSuccessful();

    $path = app_path('Payify/Drivers/PaddleDriver.php');
    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)->toContain('class PaddleDriver extends AbstractDriver');
    expect($content)->toContain("return 'paddle';");
    expect($content)->toContain('PADDLE_API_KEY');
});

it('refuses to overwrite an existing driver without force', function () {
    $this->artisan('payify:make-driver', ['name' => 'Paddle'])->assertSuccessful();
    $this->artisan('payify:make-driver', ['name' => 'Paddle'])->assertFailed();
});

it('overwrites with --force', function () {
    $this->artisan('payify:make-driver', ['name' => 'Paddle'])->assertSuccessful();
    $this->artisan('payify:make-driver', ['name' => 'Paddle', '--force' => true])->assertSuccessful();
});
