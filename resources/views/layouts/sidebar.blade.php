<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        @if (Auth::user()->role == '3')
            <li class="{{ request()->is('admin/dashboard') ? 'nav-item active' : 'nav-item' }}">
                <a href="{{ url('admin/dashboard') }}" class="nav-link"><i class="mdi mdi-grid-large menu-icon"></i>
                    <span class="menu-title">Dashboard</span>
                </a>
            </li>
            <li class="{{ request()->is('admin/categories') ? 'nav-item active' : 'nav-item' }}">
                <a href="{{ url('admin/categories') }}" class="nav-link"><i
                        class="mdi mdi-account-network menu-icon"></i>
                    <span class="menu-title">Categories</span>
                </a>
            </li>

            <li class="{{ request()->is('admin/products') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/products') }}" class="nav-link"><i class="mdi mdi-account-network menu-icon"></i>
                    <span class="menu-title">Product</span>
                </a>
            </li>

            <li class="{{ request()->is('admin/orders') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/orders') }}" class="nav-link"><i class="mdi mdi-account menu-icon"></i>
                    <span class="menu-title">Orders</span>
                </a>
            </li>



            <li class="nav-item nav-category">ADMINISTRATION</li>

            <li class="{{ request()->is('admin/users/0') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/users/0') }}" class="nav-link"><i class="mdi mdi-account-circle menu-icon"></i>
                    <span class="menu-title">Customers</span>
                </a>
            </li>
            <li class="{{ request()->is('admin/drivers') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/drivers') }}" class="nav-link"><i class="mdi mdi-account-circle menu-icon"></i>
                    <span class="menu-title">Trainees</span>
                </a>
            </li>
            <li class="{{ request()->is('admin/users/2') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/users/2') }}" class="nav-link"><i class="mdi mdi-account-circle menu-icon"></i>
                    <span class="menu-title">Producers</span>
                </a>
            </li>

            <li class="{{ request()->is('admin/users/3') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/users/3') }}" class="nav-link"><i class="mdi mdi-account-circle menu-icon"></i>
                    <span class="menu-title">Admins</span>
                </a>
            </li>



            <li
                class="{{ request()->is('admin/farmers') || request()->is('admin/farmer-details/*') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/farmers') }}" class="nav-link"><i class="mdi mdi-account-circle menu-icon"></i>
                    <span class="menu-title">Farmers</span>
                </a>
            </li>





            <li class="{{ request()->is('admin/payments') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/payments') }}" class="nav-link"><i class="mdi mdi-credit-card menu-icon"></i>
                    <span class="menu-title">Loan</span>
                </a>
            </li>


            <li class="{{ request()->is('admin/commissions') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/commissions') }}" class="nav-link"><i class="mdi mdi-credit-card menu-icon"></i>
                    <span class="menu-title">Earnings</span>
                </a>
            </li>

            <li class="{{ request()->is('admin/withdrawals') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/withdrawals') }}" class="nav-link"><i class="mdi mdi-credit-card menu-icon"></i>
                    <span class="menu-title">Withdrawals</span>
                </a>
            </li>




            <li class="nav-item nav-category">SUPPORT</li>
            <li class="{{ request()->is('admin/settings') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('admin/settings') }}" class="nav-link"><i class="mdi mdi-settings menu-icon"></i>
                    <span class="menu-title">Settings</span>
                </a>
            </li>



            <li class="nav-item nav-category">-</li>

            <li class="{{ request()->is('admin/profile') ? 'nav-item active' : 'nav-item' }}">

                <div class="dropdown-divider"></div>
                <div class="row">
                    <div class="col-md-4">
                        <a class="nav-link" href="#">
                            <i class="mdi mdi-bell menu-icon"></i>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ url('admin/profile') }}">
                            <img src="{{ Auth::user()->user_image != null ? Auth::user()->user_image : asset('template/images/faces/noimage.png') }}"
                                class="img-sm profile-pic" />
                        </a>

                    </div>

                    <div class="col-md-4">
                        <a href="#" class="nav-link"
                            onclick="event.preventDefault();
          document.getElementById('logout-form').submit();">
                            <i class="mdi mdi-logout-variant menu-icon"></i>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>

                    </div>
                </div>
            </li>
        @else
            <li class="{{ request()->is('dashboard/home') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/home') }}" class="nav-link"><i class="mdi mdi-grid-large menu-icon"></i>
                    <span class="menu-title">Dashboard</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/fundaccount') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/fundaccount') }}" class="nav-link"><i
                        class="mdi mdi-google-earth menu-icon"></i>
                    <span class="menu-title">Fund Account</span>
                </a>
            </li>


            <li class="{{ request()->is('dashboard/data') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/data') }}" class="nav-link"><i
                        class="mdi mdi-google-earth menu-icon"></i>
                    <span class="menu-title">Data</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/airtime') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/airtime') }}" class="nav-link"><i
                        class="mdi mdi-credit-card-scan menu-icon"></i>
                    <span class="menu-title">Airtime</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/electricity') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/electricity') }}" class="nav-link"><i
                        class="mdi mdi-lightbulb menu-icon"></i>
                    <span class="menu-title">Electricity</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/cable') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/cable') }}" class="nav-link"><i
                        class="mdi mdi-television-guide menu-icon"></i>
                    <span class="menu-title">Cable</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/exam') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/exam') }}" class="nav-link"><i class="mdi mdi-newspaper menu-icon"></i>
                    <span class="menu-title">Exam</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/sms') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/sms') }}" class="nav-link"><i
                        class="mdi mdi-message-reply-text menu-icon"></i>
                    <span class="menu-title">SMS</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/a2c') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/a2c') }}" class="nav-link"><i
                        class="mdi mdi-credit-card-scan menu-icon"></i>
                    <span class="menu-title">Airtime to Cash</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/transaction') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/transaction') }}" class="nav-link"><i
                        class="mdi mdi-credit-card menu-icon"></i>
                    <span class="menu-title">Transaction</span>
                </a>
            </li>

            <li class="{{ request()->is('dashboard/commissions') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/commissions') }}" class="nav-link"><i
                        class="mdi mdi-credit-card menu-icon"></i>
                    <span class="menu-title">Commissions</span>
                </a>
            </li>


            <li class="{{ request()->is('dashboard/withdrawals') ? 'nav-item active' : 'nav-item' }}">

                <a href="{{ url('dashboard/withdrawals') }}" class="nav-link"><i
                        class="mdi mdi-credit-card menu-icon"></i>
                    <span class="menu-title">Withdrawals</span>
                </a>
            </li>

            <li class="nav-item nav-category">-</li>

            <li class="{{ request()->is('dashboard/profile') ? 'nav-item active' : 'nav-item' }}">

                <div class="dropdown-divider"></div>
                <div class="row">
                    <div class="col-md-4">
                        <a class="nav-link" href="#">
                            <i class="mdi mdi-bell menu-icon"></i>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ url('dashboard/profile') }}">
                            <img src="{{ Auth::user()->user_image != null ? Auth::user()->user_image : asset('template/images/faces/noimage.png') }}"
                                class="img-sm profile-pic" />
                        </a>

                    </div>

                    <div class="col-md-4">
                        <a href="#" class="nav-link"
                            onclick="event.preventDefault();
          document.getElementById('logout-form').submit();">
                            <i class="mdi mdi-logout-variant menu-icon"></i>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                            style="display: none;">
                            @csrf
                        </form>

                    </div>
                </div>
            </li>
        @endif



    </ul>
</nav>
