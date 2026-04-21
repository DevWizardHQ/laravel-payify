<?php

arch('no debug calls')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('DTOs are readonly value classes')
    ->expect('DevWizard\Payify\Dto')
    ->toBeReadonly()
    ->toBeFinal();

arch('contracts are interfaces')
    ->expect('DevWizard\Payify\Contracts')
    ->toBeInterfaces();

arch('exceptions extend PayifyException')
    ->expect('DevWizard\Payify\Exceptions')
    ->classes()
    ->toExtend(\DevWizard\Payify\Exceptions\PayifyException::class)
    ->ignoring(\DevWizard\Payify\Exceptions\PayifyException::class);

arch('no cross-layer coupling into Http controllers from DTOs')
    ->expect('DevWizard\Payify\Dto')
    ->not->toUse('DevWizard\Payify\Http\Controllers');
