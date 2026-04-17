@extends('webapp.layout')

@section('title', __('webapp.profile_title'))

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
                                <span class="order-status status-{{ $order->status }}">
                                    {{ __('webapp.order_status_' . $order->status) }}
                                </span>
                            </span>
                            <span class="order-chevron"></span>
                        </div>

                        <p class="order-sum">
                            {{ __('webapp.order_sum') }}:
                            <strong>{{ number_format($order->total, 0, '.', ' ') }}</strong>
                        </p>

                        <p class="order-date">
                            {{ $order->created_at}}
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
                                            <p class="product-card__price">
                                                {{ $item->quantity }} ×
                                                {{ number_format($item->price, 0, '.', ' ') }}
                                            </p>
                                            <p>
                                                <b>{{ number_format($item->price * $item->quantity, 0, '.', ' ') }}</b>
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
    @include('webapp.partials.bottom-nav', ['navActive' => 'profile'])
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
