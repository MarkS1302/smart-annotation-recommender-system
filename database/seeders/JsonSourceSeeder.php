<?php

namespace Database\Seeders;

use App\Models\AnnotationSource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class JsonSourceSeeder extends Seeder
{

    public function run(): void
    {
        $createdBy = User::query()->value('id');
        $sources = [
            [
                'name' => 'Demo JSON records',
                'entity' => 'product',
                'records' => [
                    [
                        'id' => 'product-1001',
                        'name' => 'Stone Hammer',
                        'description' => 'A durable hand tool for heavy work.',
                        'status' => 'active',
                    ],
                    [
                        'id' => 'product-1002',
                        'name' => 'Torch Kit',
                        'description' => 'A compact light and battery kit.',
                        'status' => 'active',
                    ],
                    [
                        'id' => 'product-1003',
                        'name' => 'Cave Map',
                        'description' => 'A map for safe underground travel.',
                        'status' => 'draft',
                    ],
                ],
            ],
            [
                'name' => 'Demo Customer records',
                'entity' => 'customer',
                'records' => [
                    [
                        'id' => 'customer-2001',
                        'name' => 'Ada Stone',
                        'segment' => 'professional',
                        'status' => 'active',
                    ],
                    [
                        'id' => 'customer-2002',
                        'name' => 'Borin Flint',
                        'segment' => 'enterprise',
                        'status' => 'active',
                    ],
                ],
            ],
            [
                'name' => 'Demo Course records',
                'entity' => 'course',
                'records' => [
                    [
                        'id' => 'course-3001',
                        'title' => 'Stone Carving Basics',
                        'level' => 'beginner',
                        'published' => true,
                    ],
                    [
                        'id' => 'course-3002',
                        'title' => 'Advanced Cave Navigation',
                        'level' => 'advanced',
                        'published' => false,
                    ],
                ],
            ],
            [
                'name' => 'Demo Order records',
                'entity' => 'order',
                'records' => [
                    [
                        'id' => 'order-4001',
                        'order_number' => 'ORD-4001',
                        'status' => 'shipped',
                        'total' => 125.5,
                    ],
                    [
                        'id' => 'order-4002',
                        'order_number' => 'ORD-4002',
                        'status' => 'pending',
                        'total' => 80.0,
                    ],
                ],
            ],
        ];

        foreach ($sources as $sourceDefinition) {
            $source = AnnotationSource::query()->updateOrCreate(
                ['name' => $sourceDefinition['name']],
                [
                    'type' => AnnotationSource::TYPE_JSON,
                    'tag_column' => null,
                    'active' => true,
                    'created_by' => $createdBy,
                ],
            );

            $path = sprintf('annotation-sources/%s/records.json', $source->getKey());

            Storage::disk('local')->put($path, json_encode([
                'entity' => $sourceDefinition['entity'],
                'records' => $sourceDefinition['records'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            $source->update(['file_path' => $path]);
        }
    }
}
