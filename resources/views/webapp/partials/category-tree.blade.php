@php($level = $level ?? 0)

<ul class="category-tree" data-level="{{ $level }}">
    @foreach($categories as $category)
        @php($hasChildren = ($category->childrenRecursive ?? collect())->isNotEmpty())
        <li class="category-tree__item" style="margin-left: {{ $level * 14 }}px;">
            <div class="category-tree__row">
                <a href="{{ route('webapp.category.products', $category) }}" class="category-tree__link">
                    <img
                        class="category-tree__thumb"
                        loading="lazy"
                        src="{{ $category->photo_url ? asset('storage/' . $category->photo_url) : '/no-image.png' }}"
                        alt="{{ $category->localized_name }}"
                    >
                    <span class="category-tree__title">{{ $category->localized_name }}</span>
                </a>

                @if($hasChildren)
                    <button type="button"
                            class="category-tree__toggle"
                            aria-expanded="false"
                            aria-label="Показать подкатегории">
                        <span class="category-tree__chevron"></span>
                    </button>
                @endif
            </div>

            @if($hasChildren)
                <div class="category-tree__children" hidden>
                    @include('webapp.partials.category-tree', [
                        'categories' => $category->childrenRecursive,
                        'level' => $level + 1
                    ])
                </div>
            @endif
        </li>
    @endforeach
</ul>
