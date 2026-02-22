@props(['size' => 'md', 'message' => 'Carregando...'])

@php
$sizes = [
    'sm' => '24px',
    'md' => '32px',
    'lg' => '48px',
    'xl' => '64px',
];
$spinnerSize = $sizes[$size] ?? $sizes['md'];
@endphp

<div class="notion-spinner" {{ $attributes }}>
    <div class="notion-spinner-icon" style="font-size: {{ $spinnerSize }};">
        <i class="fas fa-spinner fa-spin"></i>
    </div>
    @if($message)
    <p class="notion-spinner-text">{{ $message }}</p>
    @endif
</div>
