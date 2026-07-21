@foreach($nodes as $node)
    <div>
        <a href="{{ route('knowledge.index', ['category' => $node['model']->slug]) }}"
           class="flex items-center justify-between rounded-lg px-3 py-1.5 text-sm transition {{ request('category') === $node['model']->slug ? 'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-surface-800' }}"
           style="padding-left: {{ 0.75 + ($depth ?? 0) * 0.9 }}rem">
            <span class="truncate">{{ $node['model']->name }}</span>
            <span class="text-xs text-gray-400">{{ $node['model']->articles_count }}</span>
        </a>
        @if(count($node['children']))
            @include('knowledge._category-tree', ['nodes' => $node['children'], 'depth' => ($depth ?? 0) + 1])
        @endif
    </div>
@endforeach
