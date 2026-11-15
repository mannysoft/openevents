<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BootsWithoutDatabase;

/**
 * The club-page editor writes to the Lounge database (clubs, club_images, event_sections) and to
 * events. Only a director of that club, or an admin, signed in through the Lobby may do it, and
 * only to the fields the editor shows.
 *
 * Identity is session('events_email'), which only a completed Lobby sign-in sets. session('email')
 * is not identity: POST saveUserData and the booking flow set it from form input.
 */
class ClubEditorAuthorizationTest extends TestCase
{
    use BootsWithoutDatabase;

    private const DIRECTOR = 'director.alpha@example.test';

    private const ADMIN = 'admin@example.test';

    private const ALPHA = 1;

    private const BETA = 2;

    private const ALPHA_EVENT = 101;

    private const BETA_EVENT = 202;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $this->app->forgetInstance('encrypter');

        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        config([
            'database.connections.events_test' => $sqlite,
            'database.connections.mysql_lounge' => $sqlite,
            'database.default' => 'events_test',
        ]);
        DB::purge('events_test');
        DB::purge('mysql_lounge');

        Schema::connection('events_test')->create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('club')->nullable();
            $table->text('section_two_description')->nullable();
            $table->text('who_event')->nullable();
            $table->string('event_video')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $lounge = Schema::connection('mysql_lounge');
        $lounge->create('clubs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('email')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->integer('commission_percentage')->nullable();
            $table->boolean('is_onboarded')->default(true);
            $table->text('description')->nullable();
            $table->string('director_image')->nullable();
            $table->longText('content')->nullable();
            $table->string('video_url')->nullable();
        });
        $lounge->create('club_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('club_id');
            $table->string('image_url');
            $table->boolean('is_gallery_section')->default(true);
        });
        $lounge->create('event_sections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id')->nullable();
            $table->text('section_content')->nullable();
            $table->string('section_image')->nullable();
            $table->boolean('is_placeholder')->default(false);
            $table->timestamps();
        });
        $lounge->create('lobbyusers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('email');
            $table->boolean('director')->default(false);
            $table->string('club')->nullable();
        });
        $lounge->create('user_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
        });

        $loungeDb = DB::connection('mysql_lounge');
        foreach ([self::ALPHA => 'alpha', self::BETA => 'beta'] as $id => $slug) {
            $loungeDb->table('clubs')->insert([
                'id' => $id,
                'slug' => $slug,
                'email' => "$slug.club@example.test",
                'stripe_account_id' => "acct_fixture_$slug",
                'commission_percentage' => 10,
                'description' => "$slug original",
                'content' => null,
            ]);
        }
        $loungeDb->table('club_images')->insert([
            ['id' => 501, 'club_id' => self::ALPHA, 'image_url' => 'https://img.example.test/a.jpg'],
            ['id' => 502, 'club_id' => self::BETA, 'image_url' => 'https://img.example.test/b.jpg'],
        ]);
        $loungeDb->table('event_sections')->insert([
            ['id' => 7, 'event_id' => self::ALPHA_EVENT, 'section_content' => 'alpha section'],
            ['id' => 8, 'event_id' => self::BETA_EVENT, 'section_content' => 'beta section'],
        ]);
        $loungeDb->table('lobbyusers')->insert([
            ['user_id' => 11, 'email' => self::DIRECTOR, 'director' => 1, 'club' => 'alpha'],
            ['user_id' => 12, 'email' => self::ADMIN, 'director' => 0, 'club' => null],
        ]);
        $loungeDb->table('user_roles')->insert(['user_id' => 12, 'role_id' => 1]);

        DB::table('events')->insert([
            ['id' => self::ALPHA_EVENT, 'title' => 'Alpha night', 'club' => 'alpha', 'section_two_description' => 'alpha event original'],
            ['id' => self::BETA_EVENT, 'title' => 'Beta night', 'club' => 'beta', 'section_two_description' => 'beta event original'],
        ]);

        Storage::fake('s3');
        Storage::fake(config('filesystems.default'));
    }

    private function signedIn(string $email): static
    {
        return $this->withSession(['events_email' => $email, 'email' => $email]);
    }

    private function club(int $id): object
    {
        return DB::connection('mysql_lounge')->table('clubs')->where('id', $id)->first();
    }

    private function snapshot(): array
    {
        $lounge = DB::connection('mysql_lounge');

        return [
            'clubs' => $lounge->table('clubs')->orderBy('id')->get()->toArray(),
            'club_images' => $lounge->table('club_images')->orderBy('id')->get()->toArray(),
            'event_sections' => $lounge->table('event_sections')->orderBy('id')->get()->toArray(),
            'events' => DB::table('events')->orderBy('id')->get()->toArray(),
        ];
    }

    private function assertNothingStored(): void
    {
        $this->assertSame([], Storage::disk('s3')->allFiles(), 'files reached S3');
        $this->assertSame([], Storage::disk(config('filesystems.default'))->allFiles(), 'files were stored locally');
    }

    // ---- POST /update-club ----

    public function test_anonymous_update_club_requests_are_refused_and_write_nothing(): void
    {
        $before = $this->snapshot();

        $requests = [
            'payout account' => ['content_field' => 'stripe_account_id', 'club_id' => self::ALPHA, 'content' => 'acct_attacker'],
            'editor field' => ['content_field' => 'description', 'club_id' => self::ALPHA, 'event_id' => self::ALPHA_EVENT, 'content' => 'defaced'],
            'director image' => ['content_field' => 'director_image', 'club_id' => self::ALPHA, 'content' => 'https://evil.example.test/x.png'],
            'magazine section' => ['content_field' => 'magazine_section_7', 'event_id' => self::ALPHA_EVENT, 'content' => 'defaced'],
            'gallery upload' => [
                'multiple_images_gallery' => 1,
                'target_section' => 'gallery',
                'event_id' => self::ALPHA_EVENT,
                'gallery_files' => [UploadedFile::fake()->image('g.jpg')],
            ],
        ];

        foreach ($requests as $label => $body) {
            $this->post('/update-club', $body)->assertStatus(403);
            $this->assertEquals($before, $this->snapshot(), "$label wrote to the database");
        }

        $this->assertNothingStored();
    }

    public function test_an_email_typed_into_a_public_form_is_not_a_sign_in(): void
    {
        // What POST saveUserData leaves behind when someone types the director's address.
        $this->withSession(['email' => self::DIRECTOR]);
        $before = $this->snapshot();

        $this->post('/update-club', [
            'content_field' => 'description',
            'club_id' => self::ALPHA,
            'event_id' => self::ALPHA_EVENT,
            'content' => 'defaced',
        ])->assertStatus(403);

        $this->post('/delete-image', ['id' => 501])->assertStatus(403);
        $this->post('/club-content/alpha/update', ['pages' => 'defaced'])->assertStatus(403);

        $this->assertEquals($before, $this->snapshot());
    }

    public function test_a_director_can_edit_her_own_club(): void
    {
        $this->signedIn(self::DIRECTOR)
            ->post('/update-club', [
                'content_field' => 'description',
                'club_id' => self::ALPHA,
                'event_id' => self::ALPHA_EVENT,
                'content' => '<p>New alpha copy</p>',
            ])
            ->assertRedirect();

        $this->assertSame('<p>New alpha copy</p>', $this->club(self::ALPHA)->description);
        $this->assertSame('<p>New alpha copy</p>', DB::table('events')->where('id', self::ALPHA_EVENT)->value('section_two_description'));

        // The director-image form sends no event_id.
        $this->signedIn(self::DIRECTOR)
            ->post('/update-club', [
                'content_field' => 'director_image',
                'club_id' => self::ALPHA,
                'content' => 'https://img.example.test/director.jpg',
            ])
            ->assertRedirect();

        $this->assertSame('https://img.example.test/director.jpg', $this->club(self::ALPHA)->director_image);
        $this->assertSame('beta original', $this->club(self::BETA)->description);
        $this->assertSame('beta event original', DB::table('events')->where('id', self::BETA_EVENT)->value('section_two_description'));
    }

    public function test_a_director_cannot_edit_another_club(): void
    {
        $before = $this->snapshot();

        $requests = [
            'other club' => ['content_field' => 'description', 'club_id' => self::BETA, 'content' => 'x'],
            'other club event' => ['content_field' => 'description', 'event_id' => self::BETA_EVENT, 'content' => 'x'],
            'own event, other club' => ['content_field' => 'description', 'club_id' => self::BETA, 'event_id' => self::ALPHA_EVENT, 'content' => 'x'],
            'other event, own club' => ['content_field' => 'description', 'club_id' => self::ALPHA, 'event_id' => self::BETA_EVENT, 'content' => 'x'],
            'other club magazine section' => ['content_field' => 'magazine_section_8', 'event_id' => self::ALPHA_EVENT, 'content' => 'x'],
            'unknown club' => ['content_field' => 'description', 'club_id' => 999, 'content' => 'x'],
            'no club at all' => ['content_field' => 'description', 'content' => 'x'],
            'other club gallery' => [
                'multiple_images_gallery' => 1,
                'target_section' => 'gallery',
                'event_id' => self::BETA_EVENT,
                'gallery_files' => [UploadedFile::fake()->image('g.jpg')],
            ],
        ];

        foreach ($requests as $label => $body) {
            $this->signedIn(self::DIRECTOR)->post('/update-club', $body)->assertStatus(403);
            $this->assertEquals($before, $this->snapshot(), "$label wrote to the database");
        }

        $this->assertNothingStored();
    }

    public function test_fields_outside_the_editor_are_refused_even_for_an_admin(): void
    {
        $before = $this->snapshot();

        foreach (['stripe_account_id', 'slug', 'id', 'email', 'commission_percentage', 'is_onboarded', 'content', 'video_url', 'magazine_section_x', 'club'] as $field) {
            $this->signedIn(self::ADMIN)
                ->post('/update-club', ['content_field' => $field, 'club_id' => self::ALPHA, 'event_id' => self::ALPHA_EVENT, 'content' => 'acct_attacker'])
                ->assertStatus(422);
        }

        $this->signedIn(self::ADMIN)
            ->post('/update-club', ['content_field' => ['stripe_account_id'], 'club_id' => self::ALPHA, 'content' => 'acct_attacker'])
            ->assertStatus(422);

        $this->assertEquals($before, $this->snapshot());

        // The same admin can still edit an allowed field on any club.
        $this->signedIn(self::ADMIN)
            ->post('/update-club', ['content_field' => 'description', 'club_id' => self::BETA, 'event_id' => self::BETA_EVENT, 'content' => 'admin copy'])
            ->assertRedirect();

        $this->assertSame('admin copy', $this->club(self::BETA)->description);
    }

    public function test_a_director_can_upload_gallery_images_and_edit_magazine_sections_for_her_club(): void
    {
        $this->signedIn(self::DIRECTOR)
            ->post('/update-club', [
                'multiple_images_gallery' => 1,
                'target_section' => 'gallery',
                'event_id' => self::ALPHA_EVENT,
                'club_id' => '',
                'content_field' => '',
                'gallery_files' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.png')],
            ])
            ->assertRedirect();

        $this->assertSame(2, DB::connection('mysql_lounge')->table('club_images')->where('club_id', self::ALPHA)->where('id', '!=', 501)->count());
        $this->assertCount(2, Storage::disk('s3')->allFiles());

        $this->signedIn(self::DIRECTOR)
            ->post('/update-club', ['content_field' => 'magazine_section_7', 'club_id' => '', 'event_id' => self::ALPHA_EVENT, 'content' => 'new alpha section'])
            ->assertRedirect();

        $this->assertSame('new alpha section', DB::connection('mysql_lounge')->table('event_sections')->where('id', 7)->value('section_content'));
        $this->assertSame('beta section', DB::connection('mysql_lounge')->table('event_sections')->where('id', 8)->value('section_content'));

        // Not an image.
        $this->signedIn(self::DIRECTOR)
            ->postJson('/update-club', [
                'multiple_images_gallery' => 1,
                'event_id' => self::ALPHA_EVENT,
                'gallery_files' => [UploadedFile::fake()->create('shell.php', 1, 'text/x-php')],
            ])
            ->assertStatus(422);

        $this->assertCount(2, Storage::disk('s3')->allFiles());
    }

    // ---- POST /club-content/{club}/update and /files ----

    public function test_club_content_is_written_only_by_the_clubs_editor(): void
    {
        $this->post('/club-content/alpha/update', ['pages' => 'defaced'])->assertStatus(403);
        $this->post('/club-content/alpha/files', ['uploadfiles' => [UploadedFile::fake()->image('x.jpg')]])->assertStatus(403);
        $this->signedIn(self::DIRECTOR)->post('/club-content/beta/update', ['pages' => 'defaced'])->assertStatus(403);
        $this->signedIn(self::DIRECTOR)->post('/club-content/beta/files', ['uploadfiles' => [UploadedFile::fake()->image('x.jpg')]])->assertStatus(403);

        $this->assertNull($this->club(self::ALPHA)->content);
        $this->assertNull($this->club(self::BETA)->content);
        $this->assertNothingStored();

        $this->signedIn(self::DIRECTOR)
            ->post('/club-content/alpha/update', ['pages' => [['id' => 'home']], '_token' => 'not-stored'])
            ->assertOk();

        $this->assertSame(['pages' => [['id' => 'home']]], json_decode($this->club(self::ALPHA)->content, true));

        $this->signedIn(self::DIRECTOR)
            ->post('/club-content/alpha/files', ['uploadfiles' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')]])
            ->assertOk();

        $this->assertCount(2, Storage::disk(config('filesystems.default'))->files('avatars'));
    }

    public function test_club_content_uploads_must_be_a_few_images(): void
    {
        $disk = Storage::disk(config('filesystems.default'));

        $this->signedIn(self::DIRECTOR)
            ->postJson('/club-content/alpha/files', ['uploadfiles' => [UploadedFile::fake()->create('shell.php', 1, 'text/x-php')]])
            ->assertStatus(422);

        $this->signedIn(self::DIRECTOR)
            ->postJson('/club-content/alpha/files', ['uploadfiles' => [UploadedFile::fake()->image('huge.jpg')->size(20 * 1024)]])
            ->assertStatus(422);

        $tooMany = array_map(fn ($i) => UploadedFile::fake()->image("p$i.jpg"), range(1, 11));
        $this->signedIn(self::DIRECTOR)
            ->postJson('/club-content/alpha/files', ['uploadfiles' => $tooMany])
            ->assertStatus(422);

        $this->signedIn(self::DIRECTOR)->postJson('/club-content/alpha/files', [])->assertStatus(422);

        $this->assertSame([], $disk->allFiles());
    }

    // ---- POST /delete-image ----

    public function test_delete_image_needs_the_images_club_editor(): void
    {
        $images = fn () => DB::connection('mysql_lounge')->table('club_images')->orderBy('id')->pluck('id')->all();

        $this->get('/delete-image?id=501')->assertStatus(405);
        $this->post('/delete-image', ['id' => 501])->assertStatus(403);
        $this->signedIn(self::DIRECTOR)->post('/delete-image', ['id' => 502])->assertStatus(403);
        $this->assertSame([501, 502], $images());

        $this->signedIn(self::DIRECTOR)->post('/delete-image', ['id' => 999])->assertStatus(404);

        $this->signedIn(self::DIRECTOR)->post('/delete-image', ['id' => 501])->assertOk();
        $this->assertSame([502], $images());

        $this->signedIn(self::ADMIN)->post('/delete-image', ['id' => 502])->assertOk();
        $this->assertSame([], $images());
    }

    // ---- CSRF, checked the way production checks it ----

    public function test_club_content_and_delete_image_check_the_csrf_token(): void
    {
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $session = ['events_email' => self::DIRECTOR, '_token' => 'fixture-csrf-token'];

        $this->withSession($session)->post('/club-content/alpha/update', ['pages' => 'x'])->assertStatus(419);
        $this->withSession($session)->post('/club-content/alpha/files', ['uploadfiles' => [UploadedFile::fake()->image('x.jpg')]])->assertStatus(419);
        $this->withSession($session)->post('/delete-image', ['id' => 501])->assertStatus(419);
        $this->assertNull($this->club(self::ALPHA)->content);
        $this->assertSame(2, DB::connection('mysql_lounge')->table('club_images')->count());

        // The GrapesJS editor sends the token as a header.
        $this->withSession($session)
            ->post('/club-content/alpha/update', ['pages' => 'ok'], ['X-CSRF-TOKEN' => 'fixture-csrf-token'])
            ->assertOk();
        $this->assertSame(['pages' => 'ok'], json_decode($this->club(self::ALPHA)->content, true));
    }
}
