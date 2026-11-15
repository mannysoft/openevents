<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * api/events and api/attendees were Route::resource routes from the original import. Nothing in
 * this repo or the Lounge calls them. Signed out they answered 500, because scope(null) builds
 * invalid SQL. scope(false) would silence that by dropping the account filter, which publishes
 * every attendee. So they are removed: every caller gets a 404, with or without an api_token.
 */
class ApiResourceRoutesRemovedTest extends TestCase
{
    use BootsWithoutDatabase;

    private const TOKEN = 'fixture-api-token';

    protected function setUp(): void
    {
        parent::setUp();

        // An empty sqlite database, so nothing can reach MySQL.
        config([
            'database.connections.events_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'events_test',
        ]);
        DB::purge('events_test');

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('api_token', 60)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('attendees', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('email');
            $table->string('private_reference_number');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('users')->insert(['id' => 1, 'account_id' => 7, 'api_token' => self::TOKEN]);
        DB::table('events')->insert(['id' => 1, 'account_id' => 7, 'title' => 'Fixture night']);
        DB::table('attendees')->insert([
            'id' => 1,
            'account_id' => 7,
            'email' => 'attendee@example.test',
            'private_reference_number' => 'FIXTUREREFERENCE',
        ]);
    }

    public static function paths(): array
    {
        return [
            'api/events' => ['/api/events'],
            'api/events/1' => ['/api/events/1'],
            'api/events/create' => ['/api/events/create'],
            'api/events/1/edit' => ['/api/events/1/edit'],
            'api/attendees' => ['/api/attendees'],
            'api/attendees/1' => ['/api/attendees/1'],
            'api/attendees/create' => ['/api/attendees/create'],
            'api/attendees/1/edit' => ['/api/attendees/1/edit'],
        ];
    }

    #[DataProvider('paths')]
    public function test_a_signed_out_read_gets_404(string $path): void
    {
        $this->assertNoRows($this->get($path)->assertNotFound());
    }

    #[DataProvider('paths')]
    public function test_a_valid_token_gets_404_too(string $path): void
    {
        $this->assertNoRows($this->get($path.'?api_token='.self::TOKEN)->assertNotFound());
    }

    public static function writes(): array
    {
        return [
            'POST api/events' => ['post', '/api/events'],
            'PUT api/events/1' => ['put', '/api/events/1'],
            'PATCH api/events/1' => ['patch', '/api/events/1'],
            'DELETE api/events/1' => ['delete', '/api/events/1'],
            'POST api/attendees' => ['post', '/api/attendees'],
            'PUT api/attendees/1' => ['put', '/api/attendees/1'],
            'PATCH api/attendees/1' => ['patch', '/api/attendees/1'],
            'DELETE api/attendees/1' => ['delete', '/api/attendees/1'],
        ];
    }

    #[DataProvider('writes')]
    public function test_the_empty_write_routes_are_gone(string $method, string $path): void
    {
        $this->{$method}($path)->assertNotFound();
    }

    public function test_the_route_table_holds_neither_resource(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());

        $removed = $routes
            ->map(fn ($route) => $route->uri())
            ->filter(fn ($uri) => preg_match('#^api/(events|attendees)(/|$)#', $uri));
        $this->assertSame([], $removed->values()->all());

        foreach (['events', 'attendees'] as $resource) {
            foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'] as $action) {
                $this->assertFalse(Route::has("$resource.$action"), "$resource.$action is still named");
            }
        }

        // The table was read, and the routes the Lounge calls are still in it.
        $uris = $routes->map(fn ($route) => $route->uri());
        $this->assertContains('api/saveEvents', $uris);
        $this->assertContains('api/saveFreeUser', $uris);
    }

    private function assertNoRows($response): void
    {
        $body = $response->getContent();

        $this->assertStringNotContainsString('attendee@example.test', $body);
        $this->assertStringNotContainsString('FIXTUREREFERENCE', $body);
        $this->assertStringNotContainsString('Fixture night', $body);
    }
}
