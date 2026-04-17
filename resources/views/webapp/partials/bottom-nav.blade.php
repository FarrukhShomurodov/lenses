@php
    $navActive = $navActive ?? null;
@endphp
<div class="navigation">
    <ul class="menu">
        <li class="menu__item icon-home">
            <a href="{{ route('webapp') }}" id="menu-home"
               class="{{ $navActive === 'home' ? 'is-active' : '' }}">
                <span>{{ __('webapp.menu.home') }}</span>
            </a>
        </li>

        <li class="menu__item icon-cart badge-container">
            <a href="{{ route('webapp.cart') }}" id="bottom-cart-btn"
               class="{{ $navActive === 'cart' ? 'is-active' : '' }}">
                <span>{{ __('webapp.menu.cart') }}</span>
            </a>
            <span id="bottom-cart-badge" class="cart-badge" style="display:none">0</span>
        </li>

        <li class="menu__item icon-favs">
            <a href="{{ route('webapp.favorites') }}" id="menu-favs"
               class="{{ $navActive === 'favs' ? 'is-active' : '' }}">
                <span>{{ __('webapp.menu.favorites') }}</span>
            </a>
        </li>

        <li class="menu__item icon-user">
            <a href="{{ route('webapp.profile') }}" id="menu-profile"
               class="{{ $navActive === 'profile' ? 'is-active' : '' }}">
                <span>{{ __('webapp.menu.profile') }}</span>
            </a>
        </li>
    </ul>
</div>
