@extends('webapp.layout')

@section('head')
@endsection

@section('content')
    <div class="content">
        <div id="alert-box" class="alert-box"></div>

        <div class="header">
            <a href="{{ route('webapp.profile') }}" class="header__btn i-user" id="menu-profile_header"></a>
            {{-- <img class="header__logo" src="/img/lininglogo.png" alt="logo"> --}}

            <a href="{{ route('webapp.cart') }}" class="header__btn i-cart" id="cart-btn">
                <span id="cart-badge" class="cart-badge" style="display:none">0</span>
            </a>
        </div>

        <div class="search-box">
            <form action="{{ url()->current() }}" method="get" class="search-form">
                <input type="text" name="query" id="product-search" placeholder="{{ __('webapp.search_placeholder') }}"
                       value="{{ $query ?? request('query') }}">
                <button type="submit" class="search-btn favicon-btn"></button>
            </form>
        </div>

        {{-- ORDER TYPE TABS --}}
        <div class="order-type-tabs">
            <button id="tab-btn-stock" class="order-type-tab active">Складские</button>
            <button id="tab-btn-rx" class="order-type-tab">Rx линзы</button>
        </div>

        {{-- STOCK TAB --}}
        <div id="tab-stock">

            @if (($carouselItems ?? collect())->count())
                <div class="slider" data-slider>
                    <button class="slider__btn slider__btn--prev" type="button" aria-label="Назад">‹</button>
                    <button class="slider__btn slider__btn--next" type="button" aria-label="Вперед">›</button>
                    <div class="slider__track">
                        @foreach ($carouselItems as $item)
                            <a class="slider__slide"
                               href="{{ $item->category ? route('webapp.category.products', $item->category) : route('webapp') }}">
                                <img class="slider__image" loading="lazy" src="{{ asset('storage/' . $item->image_path) }}"
                                     alt="slide">
                                @if ($item->title)
                                    <span class="slider__caption">{{ $item->title }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                    <div class="slider__dots"></div>
                </div>
            @endif

            @if (($attributes ?? collect())->isNotEmpty())
                <details class="filters-accordion">
                    <summary>Фильтры</summary>
                    <div class="filters-accordion__body">
                        <form action="{{ url()->current() }}" method="get" class="filters">
                            <input type="hidden" name="query" value="{{ $query ?? request('query') }}">
                            @if (($categoryTree ?? collect())->isNotEmpty())
                                <div class="filters__group">
                                    <div class="filters__title">Категории</div>
                                    <div class="filters__values filters__values--column">
                                        @include('webapp.partials.category-filter-options', [
                                            'categories' => $categoryTree,
                                            'selectedCategoryId' => $selectedCategoryId ?? null,
                                            'level' => 0,
                                        ])
                                    </div>
                                </div>
                            @endif
                            @foreach ($attributes as $attribute)
                                <div class="filters__group">
                                    <div class="filters__title">{{ $attribute->name }}</div>
                                    <div class="filters__values">
                                        @foreach ($attribute->values as $value)
                                            <label>
                                                <input type="checkbox" name="attributes[{{ $attribute->id }}][]"
                                                       value="{{ $value->value }}" @checked(in_array($value->value, $selectedAttributes[$attribute->id] ?? []))>
                                                <span>{{ $value->value }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            <div class="filters__actions">
                                <button type="submit">Применить</button>
                                <a href="{{ url()->current() }}">Сбросить</a>
                            </div>
                        </form>
                    </div>
                </details>
            @endif

            @foreach ($categories as $category)
                {{-- Название категории можно оставить как есть или локализовать из БД --}}
                <p class="title">{{ $category->localized_name }}</p>

                <div class="products-list">
                    @foreach ($category->products as $product)
                        @php
                            $productDiscount = (int) ($product->discount_percent ?? 0);
                            $promoType = $promotion?->active_type ?? null;
                            $promoPercent = (int) ($promotion?->discount_percent ?? 0);

                            $discountBadge = null;
                            if ($productDiscount > 0) {
                                $discountBadge = '-' . $productDiscount . '%';
                            } elseif ($promoType === \App\Models\PromotionSetting::TYPE_PERCENT && $promoPercent > 0) {
                                $discountBadge = '-' . $promoPercent . '%';
                            } elseif ($promoType === \App\Models\PromotionSetting::TYPE_ONE_PLUS_TWO) {
                                $discountBadge = '1+2';
                            }
                        @endphp

                        <div class="product__item">
                            @if ($discountBadge)
                                <span class="product__discount">{{ $discountBadge }}</span>
                            @endif
                            <div class="product">
                                <div class="product__image">
                                    <a href="{{ route('webapp.product.show', $product->id) }}">
                                        <img loading="lazy"
                                             src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->url) : '/no-image.png' }}"
                                             data-src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->url) : '/no-image.png' }}"
                                             class="lazy-img">
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

                                        <button class="product__btn add-to-cart" data-product="{{ $product->id }}">
                                        </button>

                                        <div class="product-card__btns" style="display:none" data-item-id="">
                                            <button class="product-card__plus"></button>
                                            <button class="product-card__minus"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

        </div>{{-- /tab-stock --}}

        {{-- RX TAB --}}
        <div id="tab-rx" style="display:none">
            <div class="rx-form">

                <div class="rx-eye-block">
                    <div class="rx-eye-title">Правый глаз (OD)</div>
                    <div class="rx-fields">
                        <div class="rx-field">
                            <label>SPH</label>
                            <input type="number" step="0.25" id="od_sph" placeholder="0.00">
                        </div>
                        <div class="rx-field">
                            <label>CYL</label>
                            <input type="number" step="0.25" id="od_cyl" placeholder="0.00">
                        </div>
                        <div class="rx-field">
                            <label>AXIS</label>
                            <input type="number" step="1" min="1" max="180" id="od_axis"
                                   placeholder="1–180">
                        </div>
                        <div class="rx-field">
                            <label>ADD</label>
                            <input type="number" step="0.25" id="od_add" placeholder="необяз.">
                        </div>
                        <div class="rx-field" style="grid-column:span 2">
                            <label>PRISM</label>
                            <input type="text" id="od_prism" placeholder="необяз.">
                        </div>
                    </div>
                </div>

                <div class="rx-eye-block">
                    <div class="rx-eye-title">Левый глаз (OS)</div>
                    <div class="rx-fields">
                        <div class="rx-field">
                            <label>SPH</label>
                            <input type="number" step="0.25" id="os_sph" placeholder="0.00">
                        </div>
                        <div class="rx-field">
                            <label>CYL</label>
                            <input type="number" step="0.25" id="os_cyl" placeholder="0.00">
                        </div>
                        <div class="rx-field">
                            <label>AXIS</label>
                            <input type="number" step="1" min="1" max="180" id="os_axis"
                                   placeholder="1–180">
                        </div>
                        <div class="rx-field">
                            <label>ADD</label>
                            <input type="number" step="0.25" id="os_add" placeholder="необяз.">
                        </div>
                        <div class="rx-field" style="grid-column:span 2">
                            <label>PRISM</label>
                            <input type="text" id="os_prism" placeholder="необяз.">
                        </div>
                    </div>
                </div>

                <button class="rx-order-btn" id="rx-order-btn">Оформить заказ</button>

            </div>
        </div>{{-- /tab-rx --}}

    </div>{{-- /content --}}

    {{-- RX MODAL --}}
    <div class="rx-modal-wrap" id="rxModal" style="display:none">
        <div class="rx-modal-body">
            <button type="button" class="rx-modal-close" id="closeRxModal">×</button>
            <h3>Способ доставки</h3>
            <select id="rx_delivery_type">
                <option value="pickup">Самовывоз</option>
                <option value="delivery">Доставка</option>
            </select>
            <div id="rx-delivery-fields" style="display:none">
                <input type="text" id="rx_delivery_address" placeholder="Адрес доставки">
                <input type="tel" id="rx_delivery_phone" placeholder="Телефон">
            </div>
            <button id="rx-confirm-btn">Подтвердить заказ</button>
        </div>
    </div>
@endsection

@section('nav')
    @include('webapp.partials.bottom-nav', ['navActive' => 'home'])
@endsection

@section('scripts')
    <script>
        function showError(message) {
            const box = document.getElementById('alert-box');
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

        // LAZY LOAD
        document.querySelectorAll('.lazy-img').forEach(img => {
            const real = img.dataset.src;
            const preload = new Image();
            preload.src = real;

            preload.onload = () => {
                img.src = real;
                img.classList.add('loaded');
            };
        });

        // ВОССТАНОВЛЕНИЕ КОРЗИНЫ
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

        // ОБНОВЛЕНИЕ BADGE
        function updateBadge(count) {
            if (count > 0) {
                topBadge.style.display = "block";
                topBadge.innerText = count;
            } else {
                topBadge.style.display = "none";
            }

            if (bottomBadge) {
                if (count > 0) {
                    bottomBadge.style.display = "block";
                    bottomBadge.innerText = count;
                } else {
                    bottomBadge.style.display = "none";
                }
            }
        }

        function loadCartCount() {
            if (!userId) return;

            fetch(`/api/webapp/cart/count?chat_id=${userId}`)
                .then(r => r.json())
                .then(data => updateBadge(data.count));
        }

        loadCartCount();

        // ДОБАВИТЬ В КОРЗИНУ
        document.querySelectorAll(".add-to-cart").forEach(btn => {
            btn.addEventListener("click", function() {
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

        // UPDATE QTY
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

        // SLIDER AUTO-PLAY
        const slider = document.querySelector('[data-slider]');
        if (slider) {
            const track = slider.querySelector('.slider__track');
            const slides = Array.from(slider.querySelectorAll('.slider__slide'));
            const dotsWrap = slider.querySelector('.slider__dots');
            const prevBtn = slider.querySelector('.slider__btn--prev');
            const nextBtn = slider.querySelector('.slider__btn--next');
            let index = 0;

            slides.forEach((_, i) => {
                const dot = document.createElement('span');
                dot.className = 'slider__dot' + (i === 0 ? ' active' : '');
                dot.addEventListener('click', () => {
                    index = i;
                    updateSlider();
                });
                dotsWrap.appendChild(dot);
            });

            const updateSlider = () => {
                track.style.transform = `translateX(-${index * 100}%)`;
                dotsWrap.querySelectorAll('.slider__dot').forEach((d, i) => {
                    d.classList.toggle('active', i === index);
                });
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

            setInterval(() => {
                index = (index + 1) % slides.length;
                updateSlider();
            }, 4000);
        }

        // ===== ORDER TYPE TABS =====
        const tabBtnStock = document.getElementById('tab-btn-stock');
        const tabBtnRx = document.getElementById('tab-btn-rx');
        const tabStock = document.getElementById('tab-stock');
        const tabRx = document.getElementById('tab-rx');

        tabBtnStock.addEventListener('click', () => {
            tabBtnStock.classList.add('active');
            tabBtnRx.classList.remove('active');
            tabStock.style.display = '';
            tabRx.style.display = 'none';
        });

        tabBtnRx.addEventListener('click', () => {
            tabBtnRx.classList.add('active');
            tabBtnStock.classList.remove('active');
            tabRx.style.display = '';
            tabStock.style.display = 'none';
        });

        // ===== RX ORDER =====
        const rxModal = document.getElementById('rxModal');
        const rxDeliveryType = document.getElementById('rx_delivery_type');
        const rxDeliveryFields = document.getElementById('rx-delivery-fields');

        document.getElementById('closeRxModal').addEventListener('click', () => {
            rxModal.style.display = 'none';
        });

        rxDeliveryType.addEventListener('change', () => {
            rxDeliveryFields.style.display = rxDeliveryType.value === 'delivery' ? 'block' : 'none';
        });

        document.getElementById('rx-order-btn').addEventListener('click', () => {
            const sph = document.getElementById('od_sph').value || document.getElementById('os_sph').value;
            if (!sph) {
                showError('Введите хотя бы SPH для одного глаза');
                return;
            }
            rxModal.style.display = 'flex';
        });

        document.getElementById('rx-confirm-btn').addEventListener('click', () => {
            const deliveryType = rxDeliveryType.value;
            const deliveryAddress = document.getElementById('rx_delivery_address')?.value || null;
            const deliveryPhone = document.getElementById('rx_delivery_phone')?.value || null;

            if (deliveryType === 'delivery' && (!deliveryAddress || !deliveryPhone)) {
                showError('Заполните адрес и телефон');
                return;
            }

            // Собираем массив rx: [OD, OS]
            const rx = [
                {
                    sph: document.getElementById('od_sph').value || null,
                    cyl: document.getElementById('od_cyl').value || null,
                    axis: document.getElementById('od_axis').value || null,
                    add: document.getElementById('od_add').value || null,
                    prism: document.getElementById('od_prism').value || null,
                },
                {
                    sph: document.getElementById('os_sph').value || null,
                    cyl: document.getElementById('os_cyl').value || null,
                    axis: document.getElementById('os_axis').value || null,
                    add: document.getElementById('os_add').value || null,
                    prism: document.getElementById('os_prism').value || null,
                }
            ];

            document.getElementById('rx-confirm-btn').disabled = true;

            fetch('/api/webapp/order/create-rx', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    chat_id: userId,
                    rx,
                    payment_type: 'cash',
                    delivery_type: deliveryType,
                    delivery_address: deliveryAddress,
                    delivery_phone: deliveryPhone,
                })
            })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('rx-confirm-btn').disabled = false;
                    rxModal.style.display = 'none';

                    if (data.success) {
                        tg.HapticFeedback.notificationOccurred('success');
                        ['od_sph', 'od_cyl', 'od_axis', 'od_add', 'od_prism',
                            'os_sph', 'os_cyl', 'os_axis', 'os_add', 'os_prism'
                        ].forEach(id => {
                            const el = document.getElementById(id);
                            if (el) el.value = '';
                        });
                        tabBtnStock.click();
                        const box = document.getElementById('alert-box');
                        box.innerText = 'Заказ на Rx линзы оформлен!';
                        box.style.background = '#155724';
                        box.classList.add('show');
                        setTimeout(() => {
                            box.classList.remove('show');
                            box.style.background = '';
                        }, 3000);
                    } else {
                        showError(data.message ?? 'Ошибка оформления заказа');
                    }
                })
                .catch(() => {
                    document.getElementById('rx-confirm-btn').disabled = false;
                    showError('Ошибка соединения');
                });
        });
    </script>
@endsection
