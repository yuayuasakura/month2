{{-- 作成・編集で共通のフォーム部品（@include で読み込む） --}}
{{-- old() はバリデーションエラーで戻ってきたときに入力値を復元する。第2引数は初期値 --}}

@if ($errors->any())
    <div class="alert alert-error">
        入力内容に誤りがあります。確認してください。
    </div>
@endif

<div class="form-group">
    <label for="name">タスク名</label>
    <input type="text" id="name" name="name" maxlength="200"
           value="{{ old('name', $task->name ?? '') }}">
    @error('name')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="description">詳細（任意）</label>
    <textarea id="description" name="description" rows="5" maxlength="2000">{{ old('description', $task->description ?? '') }}</textarea>
    @error('description')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="priority">重要度</label>
    <select id="priority" name="priority">
        {{-- 新規作成時の初期値は「中」 --}}
        @foreach ($priorities as $priority)
            <option value="{{ $priority->value }}"
                @selected(old('priority', ($task->priority ?? \App\Enums\TaskPriority::Medium)->value) == $priority->value)>
                {{ $priority->label() }}
            </option>
        @endforeach
    </select>
    @error('priority')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="due_date">締め切り（任意）</label>
    <input type="date" id="due_date" name="due_date"
           value="{{ old('due_date', isset($task) ? $task->due_date?->format('Y-m-d') : '') }}">
    @error('due_date')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <span class="label-title">ラベル（任意・複数選択可）</span>
    @php
        // エラーで戻ってきたときは送信した値、編集時は今ついているラベル、新規作成時は空
        $checkedLabels = old('labels', isset($task) ? $task->labels->pluck('id')->all() : []);
    @endphp
    <div class="checkbox-group">
        @foreach ($labels as $label)
            <label>
                <input type="checkbox" name="labels[]" value="{{ $label->id }}"
                    @checked(in_array($label->id, $checkedLabels))>
                {{ $label->name }}
            </label>
        @endforeach
    </div>
    @error('labels')
        <p class="error">{{ $message }}</p>
    @enderror
    @error('labels.*')
        <p class="error">{{ $message }}</p>
    @enderror
</div>
