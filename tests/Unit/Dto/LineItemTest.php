<?php

use DevWizard\Payify\Dto\LineItem;

it('stores line item fields', function () {
    $item = new LineItem(name: 'Book', price: 25.50, quantity: 2, category: 'books');

    expect($item->name)->toBe('Book');
    expect($item->price)->toBe(25.50);
    expect($item->quantity)->toBe(2);
    expect($item->category)->toBe('books');
});

it('defaults quantity to 1 and metadata to empty', function () {
    $item = new LineItem(name: 'Pen', price: 1.0);
    expect($item->quantity)->toBe(1);
    expect($item->metadata)->toBe([]);
});

it('calculates total', function () {
    $item = new LineItem(name: 'Book', price: 10.0, quantity: 3);
    expect($item->total())->toBe(30.0);
});

it('builds from array', function () {
    $item = LineItem::fromArray(['name' => 'X', 'price' => 5, 'quantity' => 2]);
    expect($item->name)->toBe('X');
    expect($item->price)->toBe(5.0);
    expect($item->quantity)->toBe(2);
});

it('exports to array', function () {
    $item = new LineItem(name: 'X', price: 5, quantity: 2, category: 'c');
    expect($item->toArray())->toBe([
        'name' => 'X',
        'price' => 5.0,
        'quantity' => 2,
        'category' => 'c',
        'metadata' => [],
    ]);
});
