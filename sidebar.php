<?php

$current_url = $_SERVER['REQUEST_URI'];

?>

<aside class="sidebar">

    <!-- LOGO -->
    <div class="logo">

        <div class="logo-icon">
            <i class="fa-solid fa-wallet"></i>
        </div>

        <div class="logo-text">
            Expense Finance
        </div>

    </div>


    <!-- SIDEBAR MENU -->
    <ul class="sidebar-menu">

        <!-- DASHBOARD -->
        <li>
            <a
                href="/expense_finance/user/dashboard.php"
                class="<?php
                    echo strpos($current_url, '/user/dashboard.php') !== false
                    ? 'active'
                    : '';
                ?>"
            >
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
        </li>


        <!-- INCOME -->
        <li>
            <a
                href="/expense_finance/user/income/index.php"
                class="<?php
                    echo strpos($current_url, '/user/income/') !== false
                    ? 'active'
                    : '';
                ?>"
            >
                <i class="fa-solid fa-money-bill-trend-up"></i>
                <span>Income</span>
            </a>
        </li>


        <!-- EXPENSE -->
        <li>
            <a
                href="/expense_finance/user/expense/index.php"
                class="<?php
                    echo strpos($current_url, '/user/expense/') !== false
                    ? 'active'
                    : '';
                ?>"
            >
                <i class="fa-solid fa-money-bill-transfer"></i>
                <span>Expense</span>
            </a>
        </li>


        <!-- CATEGORIES -->
        <li>
            <a
                href="/expense_finance/user/categories/index.php"
                class="<?php
                    echo strpos($current_url, '/user/categories/') !== false
                    ? 'active'
                    : '';
                ?>"
            >
                <i class="fa-solid fa-tags"></i>
                <span>Categories</span>
            </a>
        </li>


        <!-- REPORTS -->
        <li>
            <a
                href="/expense_finance/user/reports/index.php"
                class="<?php
                    echo strpos($current_url, '/user/reports/') !== false
                    ? 'active'
                    : '';
                ?>"
            >
                <i class="fa-solid fa-chart-column"></i>
                <span>Reports</span>
            </a>
        </li>

    </ul>


    <!-- LOGOUT -->
    <div class="logout">

        <a href="/expense_finance/logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>Logout</span>

        </a>

    </div>

</aside>