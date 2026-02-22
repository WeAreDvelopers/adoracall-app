@props(['icon' => 'inbox', 'message' => 'Nenhum item encontrado'])

<div class="notion-empty-state" {{ $attributes }}>
    <div class="notion-empty-state-icon">
        <i class="fas fa-{{ $icon }}"></i>
    </div>
    <div class="notion-empty-state-message">{{ $message }}</div>
    @if($slot != '')
    <div class="notion-empty-state-action">
        {{ $slot }}
    </div>
    @endif
</div>
