<?php

namespace Tests\Feature\Support;

use App\Models\CarBrand;
use App\Support\UniqueSlugGenerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use InvalidArgumentException;
use stdClass;
use Tests\TestCase;

class UniqueSlugGeneratorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_generates_a_unique_slug_for_any_eloquent_model(): void
    {
        CarBrand::factory()->create(['slug' => 'ford']);

        $slug = app(UniqueSlugGenerator::class)->generate(
            source: 'Ford',
            modelClass: CarBrand::class,
        );

        $this->assertSame('ford-2', $slug);
    }

    public function test_it_uses_the_given_fallback_when_source_has_no_slug_characters(): void
    {
        $slug = app(UniqueSlugGenerator::class)->generate(
            source: '!!!',
            modelClass: CarBrand::class,
            fallback: 'car-brand',
        );

        $this->assertSame('car-brand', $slug);
    }

    public function test_it_rejects_a_class_that_is_not_an_eloquent_model(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(stdClass::class.' must be an Eloquent model.');

        app(UniqueSlugGenerator::class)->generate(
            source: 'Ford',
            modelClass: stdClass::class,
        );
    }

    public function test_it_rejects_an_invalid_column_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('slug;drop is not a valid column name.');

        app(UniqueSlugGenerator::class)->generate(
            source: 'Ford',
            modelClass: CarBrand::class,
            column: 'slug;drop',
        );
    }

    public function test_it_rejects_a_fallback_that_cannot_form_a_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The fallback must contain characters that can form a slug.');

        app(UniqueSlugGenerator::class)->generate(
            source: '!!!',
            modelClass: CarBrand::class,
            fallback: '---',
        );
    }
}
