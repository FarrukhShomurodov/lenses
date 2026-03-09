@extends('webapp.layout')

@section('head')
    <style>
        .category-tree {
            list-style: none;
            margin: 0;
            padding: 0 10px;
        }

        .category-tree__item {
            margin-bottom: 8px;
        }

        .category-tree__row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-tree__link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 12px;
            border: 1px solid #eee;
            background: #fff;
            text-decoration: none;
            flex: 1;
        }

        .category-tree__toggle {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: 1px solid #eee;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .category-tree__chevron {
            display: inline-block;
            transform: rotate(90deg);
            transition: transform 0.2s ease;
            font-size: 22px;
            line-height: 1;
        }

        .category-tree__toggle[aria-expanded="true"] .category-tree__chevron {
            transform: rotate(-90deg);
        }

        .category-tree__children {
            margin-top: 6px;
        }

        .category-tree__thumb {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .category-tree__title {
            font-weight: 600;
            color: #111;
            font-size: 14px;
        }
        .header__logo{
            max-width: 120px;
        }

        .slider {
            margin: 10px -10px 20px;
            position: relative;
            overflow: hidden;
        }

        .slider__track {
            display: flex;
            transition: transform 0.4s ease;
            will-change: transform;
        }

        .slider__slide {
            min-width: 100%;
            display: block;
            text-decoration: none;
            position: relative;
        }

        .slider__image {
            width: 100%;
            height: auto;
            display: block;
        }

        .slider__caption {
            position: absolute;
            left: 12px;
            bottom: 10px;
            background: rgba(0, 0, 0, 0.65);
            color: #fff;
            font-size: 14px;
            padding: 6px 8px;
        }

        .slider__dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
        }

        .slider__dot {
            width: 6px;
            height: 6px;
            background: #111;
            opacity: 0.3;
            cursor: pointer;
        }

        .slider__dot.active {
            opacity: 1;
        }

        .slider__btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            font-size: 20px;
            border: none;
            background: #000;
            color: #fff;
            opacity: 0.75;
            cursor: pointer;
            z-index: 2;
        }

        .slider__btn:hover {
            opacity: 1;
        }

        .slider__btn--prev {
            left: 6px;
        }

        .slider__btn--next {
            right: 6px;
        }
    </style>
@endsection

@section('content')
    <div class="content">

        <div class="header">
            <a href="{{ route('webapp.profile') }}" class="header__btn i-user" id="menu-profile_header"></a>
            {{-- <img class="header__logo" src="/img/lininglogo.png" alt="logo"> --}}

            <a href="{{ route('webapp.cart') }}" class="header__btn i-cart" id="cart-btn">
                <span id="cart-badge" class="cart-badge" style="display:none">0</span>
            </a>
        </div>

        @if(($carouselItems ?? collect())->count())
            <div class="slider" data-slider>
                <button class="slider__btn slider__btn--prev" type="button" aria-label="Назад">‹</button>
                <button class="slider__btn slider__btn--next" type="button" aria-label="Вперед">›</button>
                <div class="slider__track">
                    @foreach($carouselItems as $item)
                        <a class="slider__slide"
                           href="{{ $item->category ? route('webapp.category.products', $item->category) : route('webapp') }}">
                            <img class="slider__image"
                                 loading="lazy"
                                   src="{{ asset('storage/' . $item->image_path) }}"
                                 alt="slide">
                            @if($item->title)
                                <span class="slider__caption">{{ $item->title }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
                <div class="slider__dots"></div>
            </div>
        @endif

        <div class="categories-tree">
            @include('webapp.partials.category-tree', ['categories' => $categories])
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
        document.querySelectorAll('.category-tree__toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const item = toggle.closest('.category-tree__item');
                const children = item?.querySelector(':scope > .category-tree__children');

                if (!children) return;

                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                children.hidden = isExpanded;
            });
        });

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

    </script>
@endsection
