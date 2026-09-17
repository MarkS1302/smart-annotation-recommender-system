<?php

namespace Database\Seeders;

use App\Models\AnnotationSource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use PDO;

class SqliteSourceSeeder extends Seeder
{

    public function run(): void
    {

        $source = AnnotationSource::query()->updateOrCreate(
            ['name' => 'Demo SQLite knowledge base'],
            [
                'type' => AnnotationSource::TYPE_SQLITE,
                'tag_column' => 'slug',
                'active' => true,
                'created_by' => User::query()->value('id'),
            ],
        );

        $path = sprintf('annotation-sources/%s/knowledge-base.sqlite', $source->getKey());
        $disk = Storage::disk('local');
        $disk->makeDirectory(dirname($path));

        $database = new PDO(sprintf('sqlite:%s', $disk->path($path)));
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $database->beginTransaction();
        $database->exec('DROP TABLE IF EXISTS rules');
        $database->exec('DROP TABLE IF EXISTS tags');
        $database->exec('CREATE TABLE tags (
            slug TEXT PRIMARY KEY,
            label TEXT NOT NULL,
            category TEXT NOT NULL
        )');
        $database->exec('CREATE TABLE rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entity TEXT NOT NULL,
            field TEXT,
            operator TEXT NOT NULL,
            value TEXT,
            tag_slug TEXT NOT NULL,
            priority INTEGER NOT NULL DEFAULT 0,
            rule_group TEXT,
            reason TEXT
        )');

        $tagStatement = $database->prepare(
            'INSERT INTO tags (slug, label, category) VALUES (:slug, :label, :category)',
        );

        foreach ($this->tags() as $tag) {
            $tagStatement->execute($tag);
        }

        $ruleStatement = $database->prepare(
            'INSERT INTO rules (entity, field, operator, value, tag_slug, priority, rule_group, reason)
             VALUES (:entity, :field, :operator, :value, :tag_slug, :priority, :rule_group, :reason)',
        );

        foreach ($this->rules() as $rule) {
            $ruleStatement->execute($rule);
        }

        $database->commit();
        $database = null;

        $source->update(['file_path' => $path]);
    }

    /**
     * @return array<int, array{slug: string, label: string, category: string}>
     */
    private function tags(): array
    {
        return [
            ['slug' => 'active-product', 'label' => 'Active Product', 'category' => 'status'],
            ['slug' => 'enterprise-customer', 'label' => 'Enterprise Customer', 'category' => 'type'],
            ['slug' => 'advanced-course', 'label' => 'Advanced Course', 'category' => 'type'],
            ['slug' => 'shipped-order', 'label' => 'Shipped Order', 'category' => 'status'],
        ];
    }

    /**
     * @return array<int, array{entity: string, field: string, operator: string, value: string, tag_slug: string, priority: int, rule_group: string, reason: string}>
     */
    private function rules(): array
    {
        return [
            [
                'entity' => 'product',
                'field' => 'status',
                'operator' => 'equals',
                'value' => 'active',
                'tag_slug' => 'active-product',
                'priority' => 100,
                'rule_group' => 'product-status',
                'reason' => 'The product is active.',
            ],
            [
                'entity' => 'customer',
                'field' => 'segment',
                'operator' => 'equals',
                'value' => 'enterprise',
                'tag_slug' => 'enterprise-customer',
                'priority' => 100,
                'rule_group' => 'customer-segment',
                'reason' => 'The customer belongs to the enterprise segment.',
            ],
            [
                'entity' => 'course',
                'field' => 'level',
                'operator' => 'equals',
                'value' => 'advanced',
                'tag_slug' => 'advanced-course',
                'priority' => 100,
                'rule_group' => 'course-level',
                'reason' => 'The course is intended for advanced learners.',
            ],
            [
                'entity' => 'order',
                'field' => 'status',
                'operator' => 'equals',
                'value' => 'shipped',
                'tag_slug' => 'shipped-order',
                'priority' => 100,
                'rule_group' => 'order-status',
                'reason' => 'The order has shipped.',
            ],
        ];
    }
}
