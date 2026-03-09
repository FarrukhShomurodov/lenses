@extends('webapp.layout')

@section('head')
    <style>
        .search-box {
            padding-top: 15px;
            padding-bottom: 15px;
        }

        #product-search {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #741C28;
            font-size: 14px;
            outline: none;
        }

        .search-form {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-form input {
            width: 100%;
            padding: 12px 44px 12px 14px;
            border-radius: 12px;
            border: 1px solid #ddd;
            font-size: 14px;
            outline: none;
        }

        .favicon-btn {
            position: absolute;
            right: 8px;
            width: 20px;
            height: 20px;
            border: none;
            background: url('/search.png') no-repeat center;
            background-size: contain;
            cursor: pointer;
            opacity: .75;
        }

        .favicon-btn:hover {
            opacity: 1;
        }

        .filters {
            margin: 10px 0 16px;
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 12px;
            background: #fff;
        }
        .filters__group {
            margin-bottom: 12px;
        }
        .filters__title {
            font-weight: 600;
            margin-bottom: 6px;
        }
        .filters__values {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .filters__values--column {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
        .filters__values label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: 10px;
            background: #f6f6f6;
            font-size: 13px;
        }
        .filters__category {
            width: 100%;
        }
        .filters__actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        .filters__actions button,
        .filters__actions a {
            padding: 8px 12px;
            border-radius: 10px;
            border: none;
            background: #000;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
        }

        .filters-accordion {
            margin: 10px 0 16px;
            border: 1px solid #eee;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
        }

        .filters-accordion summary {
            list-style: none;
            cursor: pointer;
            padding: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filters-accordion summary::-webkit-details-marker {
            display: none;
        }

        .filters-accordion__body {
            padding: 0 12px 12px;
        }
    </style>
@endsection

@section('content')
    <div class="content">
    <div id="alert-box" class="alert-box"></div>
        <div class="header">
            <a href="{{ url()->previous() == url()->current() ? route('webapp') : url()->previous() }}" class="header__btn i-back"></a>
            <p class="title">Все товары</p>
        </div>

        <div class="search-box">
            <form action="{{ route('webapp.products') }}" method="get" class="search-form">
                <input type="text"
                       name="query"
                       id="product-search"
                       placeholder="{{ __('webapp.search_placeholder') }}"
                       value="{{ $query ?? request('query') }}">
                <button type="submit" class="search-btn favicon-btn"></button>
            </form>
        </div>

        @if($attributes->isNotEmpty())
            <details class="filters-accordion">
                <summary>Фильтры</summary>
                <div class="filters-accordion__body">
                    <form action="{{ route('webapp.products') }}" method="get" class="filters">
                        <input type="hidden" name="query" value="{{ $query ?? request('query') }}">
                        @if(($categoriesTree ?? collect())->isNotEmpty())
                            <div class="filters__group">
                                <div class="filters__title">Категории</div>
                                <div class="filters__values filters__values--column">
                                    <label class="filters__category">
                                        <input type="radio"
                                               name="category_id"
                                               value=""
                                               @checked(empty($selectedCategoryId))>
                                        <span>Все категории</span>
                                    </label>
                                    @include('webapp.partials.category-filter-options', [
                                        'categories' => $categoriesTree,
                                        'selectedCategoryId' => $selectedCategoryId,
                                        'level' => 0
                                    ])
                                </div>
                            </div>
                        @endif
                        @foreach($attributes as $attribute)
                            <div class="filters__group">
                                <div class="filters__title">{{ $attribute->name }}</div>
                                <div class="filters__values">
                                    @foreach($attribute->values as $value)
                                        <label>
                                            <input type="checkbox"
                                                   name="attributes[{{ $attribute->id }}][]"
                                                   value="{{ $value->value }}"
                                                   @checked(in_array($value->value, $selectedAttributes[$attribute->id] ?? []))>
                                            <span>{{ $value->value }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <div class="filters__actions">
                            <button type="submit">Применить</button>
                            <a href="{{ route('webapp.products') }}">Сбросить</a>
                        </div>
                    </form>
                </div>
            </details>
        @endif

        <div class="products-list">
            @foreach($products as $product)
                @php
                    $productDiscount = (int) ($product->discount_percent ?? 0);
                    $promoType = $promotion?->active_type ?? null;
                    $promoPercent = (int) ($promotion?->discount_percent ?? 0);

                    $discountBadge = null;
                    if ($productDiscount > 0) {
                        $discountBadge = '-'.$productDiscount.'%';
                    } elseif ($promoType === \App\Models\PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
                        $discountBadge = '-'.$promoPercent.'%';
                    } elseif ($promoType === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) {
                        $discountBadge = '1+2';
                    }
                @endphp

                <div class="product__item">
                    @if($discountBadge)
                        <span class="product__discount">{{ $discountBadge }}</span>
                    @endif
                    <div class="product">
                        <div class="product__image">
                            <a href="{{ route('webapp.product.show', $product->id) }}">
                                <img
                                    loading="lazy"
                                    src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->url) : '/no-image.png' }}"
                                    class="lazy-img"
                                >
                            </a>
                        </div>

                        <div class="product__info">
                            <a href="{{ route('webapp.product.show', $product->id) }}">
                                <span class="product__type">{{ $product->localized_name }}</span>
                                <p class="product__title">
                                    {{ \Illuminate\Support\Str::limit($product->localized_description, 50, '...') }}
                                </p>
                            </a>

                            <div class="product__meta">
                                <span class="product__price">
                                    {{ number_format($product->price, 0, '.', ' ') }}
                                </span>

                                <button class="product__btn add-to-cart"
                                        data-product="{{ $product->id }}">
                                </button>

                                <div class="product-card__btns"
                                     style="display:none"
                                     data-item-id="">
                                    <button class="product-card__plus"></button>
                                    <button class="product-card__minus"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('nav')
    <div class="navigation">
        <ul class="menu">
            <li class="menu__item icon-home">
                <a href="{{ route('webapp') }}" id="menu-home"></a>
            </li>

            <li class="menu__item icon-cart badge-container">
                <a href="{{ route('webapp.cart') }}" id="bottom-cart-btn"></a>
                <span id="bottom-cart-badge" class="cart-badge" style="display:none">0</span>
            </li>

            <li class="menu__item icon-favs">
                <a href="{{ route('webapp.favorites') }}" id="menu-favs"></a>
            </li>

            <li class="menu__item icon-user">
                <a href="{{ route('webapp.profile') }}" id="menu-profile"></a>
            </li>
        </ul>
    </div>
@endsection

@section('scripts')
    <script>
        function showError(message) {
            const box = document.getElementById('alert-box');
            if (!box) return;
            box.innerText = message;
            box.classList.add('show');
            tg.HapticFeedback.notificationOccurred("error");

            setTimeout(() => {
                box.classList.remove('show');
            }, 2000);
        }

        const csrf = "{{ csrf_token() }}";
        const topBadge = document.getElementById("cart-badge");
        const bottomBadge = document.getElementById("bottom-cart-badge");

        function updateBadge(count) {
            if (topBadge) {
                topBadge.style.display = count > 0 ? "block" : "none";
                topBadge.innerText = count;
            }

            if (bottomBadge) {
                bottomBadge.style.display = count > 0 ? "block" : "none";
                bottomBadge.innerText = count;
            }
        }

        function loadCartCount() {
            if (!userId) return;

            fetch(`/api/webapp/cart/count?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => updateBadge(data.count));
        }

        loadCartCount();

        function loadCartItemsState() {
            if (!userId) return;

            fetch(`/api/webapp/cart/items?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    data.items.forEach(cartItem => {
                        const productBtn = document.querySelector(
                            `.add-to-cart[data-product="${cartItem.product_id}"]`
                        );

                        if (!productBtn) return;

                        const parent = productBtn.closest(".product__item");
                        const group = parent.querySelector(".product-card__btns");

                        productBtn.style.display = "none";
                        group.dataset.itemId = cartItem.item_id;
                        group.style.display = "flex";
                    });
                });
        }

        loadCartItemsState();

        document.querySelectorAll(".add-to-cart").forEach(btn => {
            btn.addEventListener("click", function () {
                const productId = this.dataset.product;
                const parent = this.closest(".product__item");
                const group = parent.querySelector(".product-card__btns");

                fetch("/api/webapp/cart/add", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        chat_id: userId
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) {
                            showError(data.message ?? "{{ __('webapp.add_error') }}");
                            return;
                        }

                        updateBadge(data.count);
                        btn.style.display = "none";
                        group.dataset.itemId = data.item_id;
                        group.style.display = "flex";
                        tg.HapticFeedback.notificationOccurred("success");
                    });
            });
        });

        function updateQty(itemId, delta) {
            fetch("/api/webapp/cart/update", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf
                },
                body: JSON.stringify({
                    item_id: itemId,
                    delta: delta
                })
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        showError(data.message ?? "{{ __('webapp.add_error') }}");
                        return;
                    }

                    updateBadge(data.count);

                    const group = document.querySelector(
                        `.product-card__btns[data-item-id="${itemId}"]`
                    );
                    const addBtn = group.closest(".product__item")
                        .querySelector(".add-to-cart");

                    if (data.quantity <= 0) {
                        group.style.display = "none";
                        addBtn.style.display = "block";
                    }
                });
        }

        document.querySelectorAll(".product-card__plus").forEach(btn => {
            btn.addEventListener("click", () => {
                const itemId = btn.closest(".product-card__btns").dataset.itemId;
                updateQty(itemId, +1);
            });
        });

        document.querySelectorAll(".product-card__minus").forEach(btn => {
            btn.addEventListener("click", () => {
                const itemId = btn.closest(".product-card__btns").dataset.itemId;
                updateQty(itemId, -1);
            });
        });
    </script>
@endsection
