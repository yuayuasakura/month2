<?php

namespace Tests\Unit;

use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// TaskService のユニットテスト
class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskService::class);
        $this->user = User::factory()->create();
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => '買い物',
            'description' => '牛乳を買う',
            'priority' => TaskPriority::Medium->value,
            'due_date' => '2026-12-31',
        ], $overrides);
    }

    private function labels(int $count)
    {
        return collect(range(1, $count))->map(fn ($i) => Label::create(['name' => "ラベル{$i}"]));
    }

    // ===== createTask：正常系 =====

    public function test_create_task_saves_task_for_user()
    {
        $task = $this->service->createTask($this->data(), $this->user->id);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame($this->user->id, $task->user_id);
        $this->assertSame(TaskPriority::Medium, $task->fresh()->priority);
        $this->assertSame('2026-12-31', $task->fresh()->due_date->format('Y-m-d'));
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'name' => '買い物', 'priority' => 2]);
    }

    public function test_create_task_attaches_labels()
    {
        $labels = $this->labels(2);

        $task = $this->service->createTask($this->data(['labels' => $labels->pluck('id')->all()]), $this->user->id);

        $this->assertEqualsCanonicalizing($labels->pluck('id')->all(), $task->labels->pluck('id')->all());
    }

    public function test_create_task_rejects_user_id_in_data()
    {
        // user_id は $fillable 外。開発環境では黙って無視されず例外になり、トランザクションで何も残らない
        $other = User::factory()->create();

        try {
            $this->service->createTask($this->data(['user_id' => $other->id]), $this->user->id);
            $this->fail('例外が投げられるはず');
        } catch (MassAssignmentException) {
            // 期待どおり
        }

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_create_task_with_only_name_uses_defaults()
    {
        // 任意項目なし：重要度はデフォルトの「中」、説明・締め切りは null
        $task = $this->service->createTask(['name' => '最小'], $this->user->id);

        $fresh = $task->fresh();
        $this->assertSame(TaskPriority::Medium, $fresh->priority);
        $this->assertNull($fresh->description);
        $this->assertNull($fresh->due_date);
        $this->assertCount(0, $fresh->labels);
    }

    public function test_create_task_writes_log()
    {
        Log::spy();

        $task = $this->service->createTask($this->data(), $this->user->id);

        Log::shouldHaveReceived('info')->with('Task created', ['task_id' => $task->id])->once();
    }

    // ===== createTask：異常系 =====

    public function test_create_task_with_missing_label_rolls_back()
    {
        // ラベルの紐づけで失敗したら、タスク本体の保存も取り消される（トランザクション）
        try {
            $this->service->createTask($this->data(['labels' => [99999]]), $this->user->id);
            $this->fail('例外が投げられるはず');
        } catch (QueryException) {
            // 期待どおり
        }

        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('label_task', 0);
    }

    public function test_create_task_with_missing_user_throws_exception()
    {
        $this->expectException(QueryException::class);

        $this->service->createTask($this->data(), 99999);
    }

    public function test_create_task_without_name_throws_exception()
    {
        // tasks.name は NOT NULL
        $this->expectException(QueryException::class);

        $this->service->createTask(['priority' => 1], $this->user->id);
    }

    // ===== createTask：境界値（重要度 1〜3） =====

    public static function validPriorityProvider(): array
    {
        return [
            '下限 1（低）' => [1, TaskPriority::Low],
            '中間 2（中）' => [2, TaskPriority::Medium],
            '上限 3（高）' => [3, TaskPriority::High],
        ];
    }

    #[DataProvider('validPriorityProvider')]
    public function test_create_task_with_valid_priority(int $value, TaskPriority $expected)
    {
        $task = $this->service->createTask($this->data(['priority' => $value]), $this->user->id);

        $this->assertSame($expected, $task->fresh()->priority);
    }

    public static function invalidPriorityProvider(): array
    {
        return [
            '下限の1つ下 0' => [0],
            '上限の1つ上 4' => [4],
        ];
    }

    #[DataProvider('invalidPriorityProvider')]
    public function test_create_task_with_out_of_range_priority_throws_exception(int $value)
    {
        // TaskPriority enum にない値は代入時に ValueError になり、何も保存されない
        try {
            $this->service->createTask($this->data(['priority' => $value]), $this->user->id);
            $this->fail('例外が投げられるはず');
        } catch (\ValueError) {
            // 期待どおり
        }

        $this->assertDatabaseCount('tasks', 0);
    }

    // ===== createTask：境界値（ラベル数・締め切り） =====

    public function test_create_task_with_empty_labels_array()
    {
        $task = $this->service->createTask($this->data(['labels' => []]), $this->user->id);

        $this->assertCount(0, $task->labels);
    }

    public function test_create_task_with_duplicate_label_ids_attaches_once()
    {
        $label = $this->labels(1)->first();

        $task = $this->service->createTask($this->data(['labels' => [$label->id, $label->id]]), $this->user->id);

        $this->assertCount(1, $task->labels);
        $this->assertDatabaseCount('label_task', 1);
    }

    public function test_create_task_with_200_char_name()
    {
        $task = $this->service->createTask($this->data(['name' => str_repeat('あ', 200)]), $this->user->id);

        $this->assertSame(200, mb_strlen($task->fresh()->name));
    }

    public function test_due_date_today_is_not_overdue_but_yesterday_is()
    {
        $today = $this->service->createTask($this->data(['due_date' => today()->toDateString()]), $this->user->id);
        $yesterday = $this->service->createTask($this->data(['due_date' => today()->subDay()->toDateString()]), $this->user->id);
        $tomorrow = $this->service->createTask($this->data(['due_date' => today()->addDay()->toDateString()]), $this->user->id);

        $this->assertFalse($today->fresh()->isOverdue());
        $this->assertTrue($yesterday->fresh()->isOverdue());
        $this->assertFalse($tomorrow->fresh()->isOverdue());
    }

    // ===== updateTask =====

    public function test_update_task_changes_fields_and_replaces_labels()
    {
        [$a, $b, $c] = $this->labels(3)->all();
        $task = $this->service->createTask($this->data(['labels' => [$a->id, $b->id]]), $this->user->id);

        $updated = $this->service->updateTask($task, $this->data([
            'name' => '更新後',
            'priority' => TaskPriority::High->value,
            'labels' => [$b->id, $c->id],
        ]));

        $this->assertSame('更新後', $updated->fresh()->name);
        $this->assertSame(TaskPriority::High, $updated->fresh()->priority);
        // a が外れて c が付く
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $updated->labels->pluck('id')->all());
    }

    public function test_update_task_without_labels_removes_all_labels()
    {
        // チェックを全部外すと labels 自体が送られてこない → ラベルなしになる
        $task = $this->service->createTask($this->data(['labels' => $this->labels(2)->pluck('id')->all()]), $this->user->id);

        $updated = $this->service->updateTask($task, ['name' => 'ラベルなし']);

        $this->assertCount(0, $updated->labels);
        $this->assertDatabaseCount('label_task', 0);
    }

    public function test_update_task_can_clear_due_date()
    {
        $task = $this->service->createTask($this->data(), $this->user->id);

        $updated = $this->service->updateTask($task, ['due_date' => null]);

        $this->assertNull($updated->fresh()->due_date);
    }

    public function test_update_task_with_missing_label_rolls_back()
    {
        $label = $this->labels(1)->first();
        $task = $this->service->createTask($this->data(['name' => '元の名前', 'labels' => [$label->id]]), $this->user->id);

        try {
            $this->service->updateTask($task, ['name' => '変更', 'labels' => [99999]]);
            $this->fail('例外が投げられるはず');
        } catch (QueryException) {
            // 期待どおり
        }

        // 名前もラベルも元のまま
        $fresh = Task::with('labels')->find($task->id);
        $this->assertSame('元の名前', $fresh->name);
        $this->assertSame([$label->id], $fresh->labels->pluck('id')->all());
    }

    public function test_update_task_with_invalid_priority_throws_exception()
    {
        $task = $this->service->createTask($this->data(), $this->user->id);

        $this->expectException(\ValueError::class);

        $this->service->updateTask($task, ['priority' => 99]);
    }

    // ===== deleteTask =====

    public function test_delete_task_removes_task_and_pivot_rows_but_keeps_labels()
    {
        $labels = $this->labels(2);
        $task = $this->service->createTask($this->data(['labels' => $labels->pluck('id')->all()]), $this->user->id);

        $this->service->deleteTask($task);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseCount('label_task', 0);
        // ラベル自体は他のタスクでも使うので消えない
        $this->assertDatabaseCount('labels', 2);
    }

    public function test_delete_task_writes_log()
    {
        $task = $this->service->createTask($this->data(), $this->user->id);
        Log::spy();

        $this->service->deleteTask($task);

        Log::shouldHaveReceived('info')->with('Task deleted', ['task_id' => $task->id])->once();
    }
}
