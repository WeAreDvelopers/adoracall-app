@props(['type' => 'info', 'dismissible' => false])

<div class="notion-alert notion-alert-{{ $type }}" {{ $attributes }}>
    <i class="fas fa-{{ $type === 'success' ? 'check-circle' : ($type === 'error' || $type === 'danger' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'info-circle')) }}"></i>
    <span>{{ $slot }}</span>
    @if($dismissible)
    <button type="button" class="notion-alert-close" onclick="this.parentElement.style.display='none';" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 20px; line-height: 1;">×</button>
    @endif
</div>
