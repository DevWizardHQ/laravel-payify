<?php

use DevWizard\Payify\Support\SignatureVerifier;

it('generates and verifies HMAC-SHA256', function () {
    $payload = '{"amount":100}';
    $secret = 'shh';
    $sig = SignatureVerifier::hmacSha256($payload, $secret);

    expect(SignatureVerifier::verifyHmacSha256($payload, $secret, $sig))->toBeTrue();
    expect(SignatureVerifier::verifyHmacSha256($payload, $secret, 'wrong'))->toBeFalse();
});

it('timing-safe compare', function () {
    expect(SignatureVerifier::equals('abc', 'abc'))->toBeTrue();
    expect(SignatureVerifier::equals('abc', 'abd'))->toBeFalse();
    expect(SignatureVerifier::equals('abc', 'abcd'))->toBeFalse();
});

it('base64url encode/decode roundtrip', function () {
    $data = 'hello/world+foo==';
    $encoded = SignatureVerifier::base64UrlEncode($data);

    expect($encoded)->not->toContain('+');
    expect($encoded)->not->toContain('/');
    expect($encoded)->not->toContain('=');
    expect(SignatureVerifier::base64UrlDecode($encoded))->toBe($data);
});
