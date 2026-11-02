<?php
namespace Tests\Feature;
use App\Enums\TaskPriority;
use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\LabelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TaskTest extends TestCase
{
    use RefreshDatabase;
    public function test_task_management(): void
    {
        $this->seed(LabelSeeder::class);
        $kadai = Label::where('name','課題')->first();
        $kaji = Label::where('name','家事')->first();

        // ゲストは全てログインへ
        $this->get('/tasks')->assertRedirect('/login');
        $this->get('/tasks/create')->assertRedirect('/login');
        $this->post('/tasks', ['name'=>'x','priority'=>3])->assertRedirect('/login');

        $hanako = User::factory()->create(['name'=>'花子']);
        $this->actingAs($hanako);
        $this->get('/tasks')->assertOk()->assertSee('タスクがありません');
        $this->get('/tasks/create')->assertOk()->assertSee('課題')->assertSee('家事')->assertSee('高');

        // 必須チェック・不正な重要度・存在しないラベル
        $this->post('/tasks', [])->assertSessionHasErrors(['name','priority']);
        $this->post('/tasks', ['name'=>'a','priority'=>9,'labels'=>[999]])->assertSessionHasErrors(['priority','labels.0']);

        // 詳細・締め切り・ラベルなしでも作成できる
        $this->post('/tasks', ['name'=>'低いタスク','priority'=>1])->assertRedirect();
        $low = Task::where('name','低いタスク')->first();
        $this->assertNull($low->description);
        $this->assertNull($low->due_date);
        $this->assertSame($hanako->id, $low->user_id);

        // user_id を偽装しても自分のタスクになる
        $other = User::factory()->create(['name'=>'太郎']);
        $this->post('/tasks', ['name'=>'高いタスク','description'=>'詳細です','priority'=>3,'due_date'=>'2026-12-01','labels'=>[$kadai->id,$kaji->id],'user_id'=>$other->id])->assertRedirect();
        $high = Task::where('name','高いタスク')->first();
        $this->assertSame($hanako->id, $high->user_id);
        $this->assertSame(TaskPriority::High, $high->priority);
        $this->assertSame('2026-12-01', $high->due_date->format('Y-m-d'));
        $this->assertEqualsCanonicalizing([$kadai->id,$kaji->id], $high->labels->pluck('id')->all());
        $this->post('/tasks', ['name'=>'中で締切なし','priority'=>2])->assertRedirect();
        $this->post('/tasks', ['name'=>'中で締切あり','priority'=>2,'due_date'=>'2026-12-20'])->assertRedirect();

        // 一覧：高 → 中（締め切りあり → なし） → 低
        $this->get('/tasks')->assertOk()->assertSeeInOrder(['高いタスク','中で締切あり','中で締切なし','低いタスク']);
        $this->get("/tasks/{$high->id}")->assertOk()->assertSee('重要度：高')->assertSee('詳細です')->assertSee('課題')->assertSee('2026年12月01日');
        $this->get("/tasks/{$high->id}/edit")->assertOk();

        // 更新：重要度を下げ、ラベルを全部外し、締め切りを消す
        $this->put("/tasks/{$high->id}", ['name'=>'高いタスク','priority'=>1,'due_date'=>''])->assertRedirect("/tasks/{$high->id}");
        $high->refresh();
        $this->assertSame(TaskPriority::Low, $high->priority);
        $this->assertNull($high->due_date);
        $this->assertCount(0, $high->labels);

        // 他人のタスクは見られない・編集できない・削除できない、一覧にも出ない
        $this->actingAs($other);
        $this->get('/tasks')->assertDontSee('高いタスク');
        $this->get("/tasks/{$high->id}")->assertForbidden();
        $this->get("/tasks/{$high->id}/edit")->assertForbidden();
        $this->put("/tasks/{$high->id}", ['name'=>'乗っ取り','priority'=>3])->assertForbidden();
        $this->delete("/tasks/{$high->id}")->assertForbidden();
        $this->assertSame('高いタスク', $high->fresh()->name);

        // 削除（中間テーブルの行も消える）
        $high->labels()->sync([$kadai->id]);
        $this->actingAs($hanako);
        $this->delete("/tasks/{$high->id}")->assertRedirect('/tasks');
        $this->assertNull(Task::find($high->id));
        $this->assertDatabaseMissing('label_task', ['task_id'=>$high->id]);
        $this->get('/tasks/99999')->assertNotFound();
        $this->get('/tasks/abc')->assertNotFound();
    }
}
