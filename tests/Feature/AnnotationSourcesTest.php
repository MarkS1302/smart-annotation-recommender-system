<?php

use App\Models\AnnotationSource;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('annotation sources can be searched', function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('view.annotation-sources', 'web');
    $user->givePermissionTo('view.annotation-sources');

    AnnotationSource::create([
        'name' => 'Customer records',
        'type' => AnnotationSource::TYPE_JSON,
        'created_by' => $user->id,
    ]);
    $matchingSource = AnnotationSource::create([
        'name' => 'Product knowledge base',
        'type' => AnnotationSource::TYPE_SQLITE,
        'tag_column' => 'category',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('annotation-sources.index', [
            'filter' => ['search' => 'knowledge'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'knowledge')
            ->where('sources.meta.total', 1)
            ->where('sources.data.0.id', $matchingSource->id)
            ->where('sources.data.0.name', 'Product knowledge base')
        );
});

test('sqlite annotation sources can be previewed', function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('view.annotation-sources', 'web');
    $user->givePermissionTo('view.annotation-sources');

    $disk = Storage::fake('local');
    $filePath = 'annotation-sources/1/knowledge-base.sqlite';
    $disk->put($filePath, '');

    $database = new PDO('sqlite:'.$disk->path($filePath));
    $database->exec('CREATE TABLE records (id INTEGER PRIMARY KEY, slug TEXT)');
    $database->exec("INSERT INTO records (slug) VALUES ('customer')");

    $source = AnnotationSource::create([
        'name' => 'Product knowledge base',
        'type' => AnnotationSource::TYPE_SQLITE,
        'file_path' => $filePath,
        'tag_column' => 'slug',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->getJson(route('annotation-sources.preview', $source))
        ->assertSuccessful()
        ->assertJsonPath('source.id', $source->id)
        ->assertJsonPath('tables.0.name', 'records')
        ->assertJsonPath('tables.0.rows.0.slug', 'customer');
});

test('sqlite annotation sources can be downloaded', function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('view.annotation-sources', 'web');
    $user->givePermissionTo('view.annotation-sources');

    Storage::fake('local');
    $filePath = 'annotation-sources/1/knowledge-base.sqlite';
    Storage::disk('local')->put($filePath, 'SQLite format 3');

    $source = AnnotationSource::create([
        'name' => 'Product knowledge base',
        'type' => AnnotationSource::TYPE_SQLITE,
        'file_path' => $filePath,
        'tag_column' => 'slug',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('annotation-sources.download', $source))
        ->assertSuccessful()
        ->assertHeader('Content-Disposition');
});
