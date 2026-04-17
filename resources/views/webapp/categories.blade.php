@extends('webapp.layout')

@section('content')
    <div class="content">

        <div class="header">
            <a href="{{ route('webapp.profile') }}" class="header__btn i-user" id="menu-profile_header"></a>
            <a href="{{ route('webapp.cart') }}" class="header__btn i-cart badge-container" id="cart-btn">
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

        <h2 class="title">Категории</h2>

        <div class="categories-tree">
            @include('webapp.partials.category-tree', ['categories' => $categories])
        </div>

    </div>
@endsection


@section('nav')
    @include('webapp.partials.bottom-nav', ['navActive' => 'home'])
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
            if (!box) return;
            box.innerText = message;
            box.classList.add('show');
            tg.HapticFeedback.notificationOccurred("error");

            setTimeout(() => {
                box.classList.remove('show');
            }, 2000);
        }

        const topBadge = document.getElementById("cart-badge");
        const bottomBadge = document.getElementById("bottom-cart-badge");

        document.querySelectorAll('.lazy-img').forEach(img => {
            const real = img.dataset.src;
            const preload = new Image();
            preload.src = real;

            preload.onload = () => {
                img.src = real;
                img.classList.add('loaded');
            };
        });

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
