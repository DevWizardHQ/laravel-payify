<?php

use DevWizard\Payify\Dto\Customer;

it('stores customer fields including address', function () {
    $c = new Customer(
        name: 'Iqbal', email: 'a@b.com', phone: '01700000000',
        address1: '123 Main', address2: 'Apt 4', city: 'Dhaka',
        state: 'Dhaka', postcode: '1000', country: 'BD',
    );
    expect($c->name)->toBe('Iqbal');
    expect($c->address1)->toBe('123 Main');
    expect($c->city)->toBe('Dhaka');
    expect($c->country)->toBe('BD');
    expect($c->metadata)->toBe([]);
});

it('allows all-null customer', function () {
    $c = new Customer;
    expect($c->name)->toBeNull();
    expect($c->address1)->toBeNull();
    expect($c->country)->toBeNull();
});

it('builds from array with address', function () {
    $c = Customer::fromArray([
        'name' => 'X',
        'address1' => '1 St', 'city' => 'Chittagong', 'country' => 'BD',
    ]);
    expect($c->address1)->toBe('1 St');
    expect($c->city)->toBe('Chittagong');
});

it('returns null when array is null', function () {
    expect(Customer::fromArray(null))->toBeNull();
});

it('exports to array with address', function () {
    $c = new Customer(name: 'X', address1: '1 St', country: 'BD');
    $out = $c->toArray();
    expect($out)->toMatchArray([
        'name' => 'X',
        'address1' => '1 St',
        'country' => 'BD',
    ]);
    expect($out)->toHaveKey('email');
    expect($out)->toHaveKey('phone');
});
