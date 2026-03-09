@extends('webapp.layout')

@section('title', __('webapp.profile_title'))

@section('head')

    <style>

        .profile-header {

            background: #fff;

            border-radius: 18px;

            display: flex;

            align-items: center;

            gap: 18px;

            margin-bottom: 25px;

        }

        .profile-avatar img {

            width: 70px;

            height: 70px;

            border-radius: 50%;

            object-fit: cover;

            border: 3px solid #fff;

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);

        }

        .profile-name {

            display: flex;

            flex-direction: column;

            gap: 4px;

        }

        .profile-name strong {

            font-size: 18px;

            color: #111;

        }

        .profile-name span {

            font-size: 15px;

            color: #666;

        }

        .subtitle {

            font-size: 18px;

            margin-bottom: 12px;

            font-weight: bold;

            color: #222;

        }

        .order-card {

            background: #fff;

            padding: 14px 0;

            border-radius: 14px;

            margin-bottom: 12px;

            font-size: 15px;

            cursor: pointer;

        }

        .order-line {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 6px;

            font-weight: 600;

        }

        .order-line-left {

            display: flex;

            align-items: center;

            gap: 6px;

        }

        .order-chevron {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            width: 22px;

            height: 22px;

            transition: transform 0.3s ease;

            flex-shrink: 0;

        }

        .order-chevron::after {

            content: '';

            display: block;

            width: 7px;

            height: 7px;

            border-right: 2px solid #999;

            border-bottom: 2px solid #999;

            transform: rotate(45deg);

            margin-top: -2px;

        }

        .order-card.open .order-chevron {

            transform: rotate(180deg);

        }

        .order-status {

            padding: 4px 8px;

            border-radius: 6px;

            font-size: 13px;

            font-weight: 600;

        }

        .status-new {

            background: #dbeafe;

            color: #1e40af;

        }

        .status-in_process {

            background: #fef3c7;

            color: #b45309;

        }

        .status-done {

            background: #d1fae5;

            color: #065f46;

        }

        .status-canceled {

            background: #fee2e2;

            color: #b91c1c;

        }

        .order-items {

            display: none;

            margin-top: 10px;

        }

        .order-card.open .order-items {

            display: block;

        }

        .empty-text {

            margin-top: 15px;

            color: #999;

            font-size: 15px;

        }

        /* Фикс: отступ снизу чтобы навигация не перекрывала контент */

        .content {

            padding-bottom: 80px;

        }

    </style>

@endsection

@section('content')

    <div class="content">

        <div class="header">

            <a href="{{ url()->previous() == url()->current() ? route('webapp') : url()->previous() }}"

                class="header__btn i-back"></a>

            <p class="title">{{ __('webapp.profile_title') }}</p>

        </div>

        <div class="products">

            <div class="profile-header">

                <div class="profile-avatar">

                    <img id="avatar-img" src="/img/user-placeholder.jpg">

                </div>

                <div class="profile-name">

                    <strong>{{ $user->first_name }} {{ $user->second_name }}</strong>

                    <span>{{ $user->phone }}</span>

                </div>

            </div>

            <div class="profile">

                <h3 class="subtitle">{{ __('webapp.my_orders') }}</h3>

                @forelse($orders as $order)

                    <div class="order-card js-order" data-order-id="{{ $order->id }}">

                        <div class="order-line">

                            <span class="order-line-left">

                                {{ __('webapp.order_number') }} {{ $order->id }}

                                <span class="order-chevron"></span>

                            </span>

                            <span class="order-status status-{{ $order->status }}">

                                {{ __('webapp.order_status_' . $order->status) }}

                            </span>

                        </div>

                        <p class="order-sum">

                            {{ __('webapp.order_sum') }}:

                            <strong>{{ number_format($order->total, 0, '.', ' ') }}</strong>

                        </p>

                        <p class="order-date">

                            {{ $order->created_at->format('d.m.Y H:i') }}

                        </p>

                        <div class="order-items" id="order-items-{{ $order->id }}">

                            @foreach ($order->items as $item)

                                <div class="product-card" style="margin-top:10px;">

                                    <div class="product-card__image">

                                        <img

                                            src="{{ $item->product && $item->product->images->first()

                                                ? asset('storage/' . $item->product->images->first()->url)

                                                : '/no-image.png' }}">

                                    </div>

                                    <div class="product-card__data">

                                        <div class="product-card__title">

                                            {{ $item->product ? $item->product->localized_name : 'Товар удален' }}

                                        </div>

                                        <div class="product-card__meta">

                                            <p>

                                                {{ $item->quantity }} ×

                                                {{ number_format($item->price, 0, '.', ' ') }}

                                            </p>

                                            <p>

                                                <b>

                                                    {{ number_format($item->price * $item->quantity, 0, '.', ' ') }}

                                                </b>

                                            </p>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                @empty

                    <p class="empty-text">{{ __('webapp.orders_empty') }}</p>

                @endforelse

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

        const user = tg.initDataUnsafe?.user;

        if (user?.photo_url) {

            document.getElementById("avatar-img").src = user.photo_url;

        }

        document.querySelectorAll('.js-order').forEach(order => {

            order.addEventListener('click', function() {

                this.classList.toggle('open');

            });

        });

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

    </script>

@endsection