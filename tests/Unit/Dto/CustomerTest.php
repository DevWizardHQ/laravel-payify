<?php

use DevWizard\Payify\Dto\Customer;

it('stores customer fields', function () {
    $c = new Customer(name: 'Iqbal', email: 'a@b.com', phone: '01700000000');
    expect($c->name)->toBe('Iqbal');
    expect($c->email)->toBe('a@b.com');
    expect($c->phone)->toBe('01700000000');
    expect($c->metadata)->toBe([]);
});

it('allows all-null customer', function () {
    $c = new Customer();
    expect($c->name)->toBeNull();
    expect($c->email)->toBeNull();
    expect($c->phone)->toBeNull();
});

it('accepts metadata', function () {
    $c = new Customer(name: 'X', metadata: ['locale' => 'bn']);
    expect($c->metadata)->toBe(['locale' => 'bn']);
});

it('builds from array', function () {
    $c = Customer::fromArray(['name' => 'Iqbal', 'email' => 'a@b.com']);
    expect($c->name)->toBe('Iqbal');
    expect($c->email)->toBe('a@b.com');
    expect($c->phone)->toBeNull();
});

it('returns null when array is null', function () {
    expect(Customer::fromArray(null))->toBeNull();
});

it('exports to array', function () {
    $c = new Customer(name: 'X', email: 'y@z.com');
    expect($c->toArray())->toBe([
        'name' => 'X',
        'email' => 'y@z.com',
        'phone' => null,
        'metadata' => [],
    ]);
});
