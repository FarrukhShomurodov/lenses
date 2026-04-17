@extends('webapp.layout')

@section('head')
@endsection

@section('content')
    <div class="content is-product">
        <div id="alert-box" class="alert-box"></div>

        <div class="single">
            <div class="single__photo">
                <div class="header">
                    <a href="{{ url()->previous() == url()->current() ? route('webapp') : url()->previous() }}" class="header__btn i-back"></a>
                    <a href="#" class="header__btn i-favs" id="fav-btn" data-id="{{ $product->id }}"></a>
                </div>

                @php
                    $productDiscount = (int) ($product->discount_percent ?? 0);
                    $promoType = $promotion?->active_type ?? null;
                    $promoPercent = (int) ($promotion?->discount_percent ?? 0);

                    $basePrice = (float) $product->price;
                    $finalPrice = $basePrice;

                    $discountBadge = null;
                    if ($productDiscount > 0) {
                        $discountBadge = '-' . $productDiscount . '%';
                        $finalPrice = ($basePrice * (100 - $productDiscount)) / 100;
                    } elseif ($promoType === \App\Models\PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
                        $discountBadge = '-' . $promoPercent . '%';
                        $finalPrice = ($basePrice * (100 - $promoPercent)) / 100;
                    } elseif ($promoType === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) {
                        $discountBadge = '1+2';
                    }

                    $attributesGrouped = $product->attributes->groupBy('name');

                    // Static product fields → label map (skip empties)
                    $specPairs = collect([
                        'Артикул'       => $product->article,
                        'Производитель' => $product->manufacturer,
                        'Модель'        => $product->model,
                        'Семейство'     => $product->family,
                        'Покрытие'      => $product->coating,
                        'Индекс'        => $product->index,
                        'SPH'           => $product->sph,
                        'CYL'           => $product->cyl,
                        'AXIS'          => $product->axis,
                        'Цвет'          => $product->color,
                        'Опция'         => $product->option,
                    ])->filter(fn ($v) => $v !== null && $v !== '');

                @endphp

                @if ($product->images->count())
                    <div class="product-slider" data-product-slider>
                        <div class="product-slider__track">
                            @foreach ($product->images as $image)
                                <div class="product-slider__slide">
                                    <img src="{{ asset('storage/' . $image->url) }}" alt="image">
                                </div>
                            @endforeach
                        </div>
                        @if ($product->images->count() > 1)
                            <button class="product-slider__btn product-slider__btn--prev" type="button">‹</button>
                            <button class="product-slider__btn product-slider__btn--next" type="button">›</button>
                            <div class="product-slider__dots"></div>
                        @endif
                    </div>
                @else
                    <div class="product-slider">
                        <img src="/no-image.png" alt="image">
                    </div>
                @endif

                @if ($discountBadge)
                    <span class="product__discount single__photo-badge">{{ $discountBadge }}</span>
                @endif

            </div>

            <div class="single__info">
                @if ($product->category)
                    <span class="single__chip">
                        {{ $product->category->localized_name ?? $product->category->name }}
                    </span>
                @endif

                <h1 class="single__title">{{ $product->localized_name }}</h1>

                <div class="single__price-row">
                    <div class="single__price-block">
                        <span class="single__price-current">{{ number_format($finalPrice, 0, '.', ' ') }}</span>
                        @if ($finalPrice < $basePrice)
                            <span class="single__price-old">{{ number_format($basePrice, 0, '.', ' ') }}</span>
                        @endif
                    </div>
                    @if ($product->stock)
                        <span class="single__stock {{ $product->stock->quantity > 0 ? 'is-available' : 'is-empty' }}">
                            <span class="single__stock-dot"></span>
                            @if ($product->stock->quantity > 0)
                                В наличии: {{ $product->stock->quantity }}
                            @else
                                Нет в наличии
                            @endif
                        </span>
                    @endif
                </div>

                <div class="single__cta">
                    <button class="single__button add-to-cart" data-id="{{ $product->id }}">
                        Купить
                    </button>

                    <div class="qty-controls" id="qty-controls">
                        <button class="product-card__minus qty-minus" aria-label="Уменьшить"></button>
                        <span id="qty-value">1</span>
                        <button class="product-card__plus qty-plus" aria-label="Увеличить"></button>
                    </div>
                </div>

                @if ($product->localized_description)
                    <div class="single__section">
                        <h3 class="single__section-title">Описание</h3>
                        <div class="single__desc">
                            <p>{{ $product->localized_description }}</p>
                        </div>
                    </div>
                @endif

                @if ($specPairs->isNotEmpty() || $attributesGrouped->isNotEmpty())
                    <div class="single__section">
                        <h3 class="single__section-title">Характеристики</h3>
                        <dl class="single__attrs">
                            @foreach ($specPairs as $label => $value)
                                <div class="single__attr">
                                    <dt>{{ $label }}</dt>
                                    <dd>{{ $value }}</dd>
                                </div>
                            @endforeach

                            @foreach ($attributesGrouped as $attributeName => $items)
                                <div class="single__attr">
                                    <dt>{{ $attributeName }}</dt>
                                    <dd>{{ $items->pluck('pivot.value')->unique()->implode(', ') }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

@section('nav')
    @include('webapp.partials.bottom-nav')
@endsection

@section('scripts')
    <script>
        function showError(message) {
            const box = document.getElementById('alert-box');
            box.innerText = message;
            box.classList.add('show');
            tg.HapticFeedback.notificationOccurred("error");
            setTimeout(() => box.classList.remove('show'), 2000);
        }

        function updateBadge(count) {
            const topBadge = document.getElementById("cart-badge");
            const bottomBadge = document.getElementById("bottom-cart-badge");

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

        const csrf = "{{ csrf_token() }}";
        const favBtn = document.getElementById("fav-btn");
        const productId = favBtn?.dataset?.id;

        // ---------- FAVORITE ----------
        function loadFavoriteStatus() {
            if (!userId || !productId) return;

            fetch(`/api/webapp/favorite/check?chat_id=${userId}&product_id=${productId}`)
                .then(r => r.json())
                .then(data => {
                    favBtn.classList.toggle("active", data.favorite);
                });
        }

        loadFavoriteStatus();

        favBtn.addEventListener("click", function(e) {
            e.preventDefault();

            fetch("/api/webapp/favorite/toggle", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrf
                    },
                    body: JSON.stringify({
                        chat_id: userId,
                        product_id: productId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    favBtn.classList.toggle("active", data.favorite);
                    tg.HapticFeedback.impactOccurred(data.favorite ? "medium" : "light");
                });
        });

        // ---------- PRODUCT SLIDER ----------
        const productSlider = document.querySelector('[data-product-slider]');
        if (productSlider) {
            const track = productSlider.querySelector('.product-slider__track');
            const slides = Array.from(productSlider.querySelectorAll('.product-slider__slide'));
            const dotsWrap = productSlider.querySelector('.product-slider__dots');
            const prevBtn = productSlider.querySelector('.product-slider__btn--prev');
            const nextBtn = productSlider.querySelector('.product-slider__btn--next');
            let index = 0;

            if (dotsWrap) {
                slides.forEach((_, i) => {
                    const dot = document.createElement('span');
                    dot.className = 'product-slider__dot' + (i === 0 ? ' active' : '');
                    dot.addEventListener('click', () => {
                        index = i;
                        updateSlider();
                    });
                    dotsWrap.appendChild(dot);
                });
            }

            const updateSlider = () => {
                track.style.transform = `translateX(-${index * 100}%)`;
                if (dotsWrap) {
                    dotsWrap.querySelectorAll('.product-slider__dot').forEach((d, i) => {
                        d.classList.toggle('active', i === index);
                    });
                }
            };

            if (prevBtn && nextBtn) {
                prevBtn.addEventListener('click', () => {
                    index = (index - 1 + slides.length) % slides.length;
                    updateSlider();
                });
                nextBtn.addEventListener('click', () => {
                    index = (index + 1) % slides.length;
                    updateSlider();
                });
            }
        }

        // ---------- CART ----------
        function loadProductCartState() {
            fetch(`/api/webapp/cart/items?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => {
                    const found = data.items.find(i => i.product_id == productId);
                    if (found) {
                        document.querySelector(".add-to-cart").style.display = "none";
                        const controls = document.getElementById("qty-controls");
                        controls.dataset.itemId = found.item_id;
                        document.getElementById("qty-value").innerText = found.qty;
                        controls.style.display = "flex";
                    }
                });
        }

        loadProductCartState();

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

                    const controls = document.getElementById("qty-controls");

                    if (data.quantity <= 0) {
                        controls.style.display = "none";
                        document.querySelector(".add-to-cart").style.display = "block";
                        return;
                    }

                    document.getElementById("qty-value").innerText = data.quantity;
                });
        }

        document.querySelector(".add-to-cart").addEventListener("click", function() {
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
                    this.style.display = "none";

                    const controls = document.getElementById("qty-controls");
                    controls.dataset.itemId = data.item_id;
                    document.getElementById("qty-value").innerText = 1;
                    controls.style.display = "flex";

                    tg.HapticFeedback.notificationOccurred("success");
                });
        });

        document.querySelector(".qty-plus").addEventListener("click", () => {
            const itemId = document.getElementById("qty-controls").dataset.itemId;
            updateQty(itemId, +1);
        });

        document.querySelector(".qty-minus").addEventListener("click", () => {
            const itemId = document.getElementById("qty-controls").dataset.itemId;
            updateQty(itemId, -1);
        });
    </script>
@endsection
