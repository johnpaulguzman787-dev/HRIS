<div class="bg-white rounded-xl p-6 card-hover" style="box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
    <div class="flex items-center justify-between mb-2">
        <span class="text-gray-500 text-sm">{{ $title }}</span>
        <span class="w-10 h-10 {{ $iconBg }} rounded-lg flex items-center justify-center">
            {!! $icon !!}
        </span>
    </div>
    <p class="text-3xl font-bold text-gray-800">{{ $value }}</p>
    @if(isset($trend))
    <p class="text-xs {{ $trendColor }} mt-2">{{ $trend }}</p>
    @endif
</div>