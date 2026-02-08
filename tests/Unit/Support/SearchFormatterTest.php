<?php

use App\Support\Planner\SearchFormatter;

it('returns null when search is null', function () {
    expect(SearchFormatter::clean(null))->toBeNull();
});

it('returns null when search is empty', function () {
    expect(SearchFormatter::clean(''))->toBeNull();
});

it('returns title unchanged without suffix', function () {
    expect(SearchFormatter::clean('Classic Fish Tacos'))
        ->toBe('Classic Fish Tacos');
});

it('removes community suffix', function () {
    expect(SearchFormatter::clean('Classic Fish Tacos (community)'))
        ->toBe('Classic Fish Tacos');
});

it('removes user suffix', function () {
    expect(SearchFormatter::clean('Classic Fish Tacos (Admin)'))
        ->toBe('Classic Fish Tacos');
});

it('handles whitespace', function () {
    expect(SearchFormatter::clean('  Classic Fish Tacos (Admin)  '))
        ->toBe('Classic Fish Tacos');
});

it('does not strip parentheses inside title', function () {
    expect(SearchFormatter::clean('Fish Tacos (Spicy Style) Deluxe'))
        ->toBe('Fish Tacos (Spicy Style) Deluxe');
});

it('handles complex usernames', function () {
    expect(SearchFormatter::clean('Creamy Lasagna (User Xyz 123)'))
        ->toBe('Creamy Lasagna');
});
