@extends('webapp.layout')

@section('head')
    <style>
        .qty-controls {
            display: none;
            align-items: center;
            gap: 10px;
        }

        .qty-controls span {
            font-size: 18px;
            font-weight: 700;
        }

        .single__photo {
            position: relative;
        }

        .single__photo-wave {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            line-height: 0;
            height: 70px;
            z-index: 10;
            pointer-events: none;
        }

        .single__photo-wave svg {
            display: block;
            width: 100%;
            height: 69px;
        }

        .product__discount {
            background: #d70000;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            line-height: 1;
            border-radius: 6px;
        }

        .single__row {
            position: relative;
        }

        .product__discount-badge {
            position: absolute;
            right: 0;
            top: -25px;
            z-index: 3;
        }

        .single__button,
        .product-card__plus,
        .product-card__minus {
            background-color: #000 !important;
            color: #fff !important;
        }

        .product__price {
            font-size: 18px;
            font-weight: 700;
        }

        .product__price-old {
            font-size: 14px;
            color: #999;
            text-decoration: line-through;
            margin-left: 8px;
        }

        .product__meta-list {
            margin-top: 10px;
            font-size: 14px;
            color: #444;
        }

        .product__meta-list div {
            margin-top: 4px;
        }

        .product-slider {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            z-index: 1;
            margin-top: -110px;
            margin-bottom: 0;
        }

        .single__info {
            margin-top: 0;
            padding-top: 24px;
            background: #fff;
            position: relative;
            z-index: 2;
        }

        .single__title {
            font-size: 16px;
            line-height: 1.2;
        }

        .product-slider__track {
            display: flex;
            transition: transform 0.4s ease;
            will-change: transform;
        }

        .product-slider__slide {
            min-width: 100%;
            position: relative;
        }

        .product-slider__slide img {
            position: static;
            width: 100%;
            display: block;
            border-radius: 12px;
            height: 100%;
            object-fit: cover;
        }

        .product-slider__dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
        }

        .product-slider__dot {
            width: 6px;
            height: 6px;
            background: #111;
            opacity: 0.3;
            cursor: pointer;
        }

        .product-slider__dot.active {
            opacity: 1;
        }

        .product-slider__btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: none;
            background: #000;
            color: #fff;
            opacity: 0.75;
            cursor: pointer;
            z-index: 2;
        }

        .product-slider__btn--prev {
            left: 6px;
        }

        .product-slider__btn--next {
            right: 6px;
        }
    </style>
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

                <div class="single__photo-wave" aria-hidden="true">
                    <svg viewBox="0 0 1440 100" preserveAspectRatio="none">
                        <path fill="#FFFFFF" d="M0,60 C240,100 480,20 720,60 C960,100 1200,20 1440,60 L1440,100 L0,100 Z">
                        </path>
                    </svg>
                </div>
            </div>

            <div class="single__info">
                <div class="single__row">
                    <h1 class="single__title">{{ $product->localized_name }}</h1>

                    @if ($discountBadge)
                        <span class="product__discount product__discount-badge">{{ $discountBadge }}</span>
                    @endif

                    <button class="single__button add-to-cart" data-id="{{ $product->id }}">
                    </button>

                    <div class="qty-controls" id="qty-controls">
                        <button class="product-card__plus qty-plus"></button>
                        <span id="qty-value">1</span>
                        <button class="product-card__minus qty-minus"></button>
                    </div>
                </div>

                <div class="single__desc">
                    <p>{{ $product->localized_description }}</p>
                </div>

                <div class="product__meta-list">
                    <div>
                        Цена:
                        <span class="product__price">{{ number_format($finalPrice, 0, '.', ' ') }}</span>
                        @if ($finalPrice < $basePrice)
                            <span class="product__price-old">{{ number_format($basePrice, 0, '.', ' ') }}</span>
                        @endif
                    </div>

                    @if ($product->category)
                        <div>Категория: {{ $product->category->localized_name ?? $product->category->name }}</div>
                    @endif

                    @if ($product->stock)
                        <div>В наличии: {{ $product->stock->quantity }}</div>
                    @endif

                    @if ($attributesGrouped->isNotEmpty())
                        <div>
                            Атрибуты:
                            @foreach ($attributesGrouped as $attributeName => $items)
                                <div>
                                    <strong>{{ $attributeName }}:</strong>
                                    {{ $items->pluck('pivot.value')->unique()->implode(', ') }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
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
