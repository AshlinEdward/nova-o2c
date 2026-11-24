
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Nova O2C ERP')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar: #17243a;
            --sidebar-hover: #263b59;
            --accent: #3978e8;
            --page: #f4f7fb;
        }

        body {
            background: var(--page);
            color: #253247;
            font-family: "Segoe UI", sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--sidebar);
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 15px;
            z-index: 1000;
            scrollbar-width: thin;
            scrollbar-color: #52647e #17243a;
        }

        .brand {
            color: #fff;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 0 12px 25px;
            border-bottom: 1px solid #34445d;
        }

        .sidebar-label {
            color: #91a2bb;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.5px;
            padding: 22px 12px 8px;
            text-transform: uppercase;
        }

        .side-link {
            display: block;
            color: #c4cede;
            text-decoration: none;
            padding: 10px 13px;
            margin: 3px 0;
            border-radius: 7px;
            font-size: 13px;
            transition: .2s;
        }

        .side-link:hover,
        .side-link.active {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .side-link.active {
            border-left: 3px solid #6da1ff;
        }

        .main-area {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e7ebf1;
            padding: 15px 30px;
        }

        .content-area {
            padding: 30px;
        }

        .page-title {
            font-size: 25px;
            font-weight: 700;
            color: #1d2b42;
        }

        .panel,
        .stat-card,
        .module-card {
            background: #fff;
            border: 1px solid #e9edf3;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(25, 42, 70, .04);
        }

        .stat-card {
            padding: 20px;
            height: 100%;
        }

        .stat-label {
            color: #7b8798;
            font-size: 12px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 750;
            color: #20314c;
            margin-top: 7px;
        }

        .module-card {
            padding: 20px;
            height: 100%;
            transition: .2s;
        }

        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(25, 42, 70, .08);
        }

        .module-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf3ff;
            color: var(--accent);
            border-radius: 9px;
            font-weight: 800;
            margin-bottom: 13px;
        }

        .table thead th {
            color: #65738a;
            background: #f7f9fc;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 14px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 13px 14px;
            font-size: 13px;
            vertical-align: middle;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #eaf1ff;
            color: #3268c8;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 65px;
                padding: 15px 7px;
                overflow-x: hidden;
            }

            .brand {
                font-size: 11px;
                padding: 10px 2px;
                letter-spacing: 0;
            }

            .sidebar-label {
                display: none;
            }

            .side-link {
                font-size: 0;
                text-align: center;
                padding: 13px 2px;
            }

            .side-link::first-letter {
                font-size: 15px;
            }

            .main-area {
                margin-left: 65px;
            }

            .content-area {
                padding: 18px 12px;
            }

            .topbar {
                padding: 13px;
            }
        }
    </style>
</head>

<body>

@php
    $currentUser = auth()->user();
    $currentRoute = request()->route()?->getName();
    $currentModule = request()->route('module');
    $userRoles = $currentUser->roles->pluck('name')->all();

    $navigation = [
        'Masters' => [
            ['Customers', 'customers.index', null, 'C', ['Admin', 'Sales Executive', 'Sales Manager']],
            ['Products', 'products.index', null, 'P', ['Admin', 'Sales Executive', 'Sales Manager', 'Warehouse', 'Accounts']],
            ['Warehouses', 'warehouses.index', null, 'W', ['Admin', 'Warehouse']],
        ],

        'Operations' => [
            ['Inventory', 'inventory.index', null, 'I', ['Admin', 'Warehouse']],
            ['Sales Orders', 'orders.index', null, 'O', ['Admin', 'Sales Executive', 'Sales Manager']],
            ['Credit Management', 'overview', 'credit', 'C', ['Admin', 'Sales Manager']],
            ['Shipments', 'shipments.index', null, 'S', ['Admin', 'Warehouse']],
        ],

        'Finance & Reports' => [
            ['Invoices', 'invoices.index', null, 'I', ['Admin', 'Accounts']],
            ['Payments', 'payments.index', null, 'P', ['Admin', 'Accounts']],
            ['Sales Returns', 'returns.index', null, 'R', ['Admin', 'Accounts']],
            ['Reports', 'reports.index', null, 'R', ['Admin', 'Sales Manager', 'Accounts']],
        ],
    ];
@endphp

<aside class="sidebar">

    <div class="brand">
        NOVA <span style="color:#78a8ff">O2C</span>
    </div>

    <div class="sidebar-label">Workspace</div>

    <a href="{{ route('dashboard') }}"
       class="side-link {{ $currentRoute === 'dashboard' ? 'active' : '' }}">
        ◈ &nbsp; Dashboard
    </a>

    @foreach($navigation as $section => $links)

        <div class="sidebar-label">{{ $section }}</div>

        @foreach($links as [$label, $routeName, $moduleName, $icon, $allowedRoles])

            @if(count(array_intersect($userRoles, $allowedRoles)) > 0)

                @php
                    $active = $routeName === 'overview'
                        ? ($currentRoute === 'overview' && $currentModule === $moduleName)
                        : (
                            $currentRoute === $routeName ||
                            ($routeName === 'warehouses.index' &&
                                in_array($currentRoute, [
                                    'warehouses.create',
                                    'warehouses.edit',
                                    'warehouses.store',
                                    'warehouses.update',
                                    'warehouses.toggle-status',
                                ]))
                        );
                @endphp

                <a href="{{ $routeName === 'overview'
                        ? route('overview', $moduleName)
                        : route($routeName) }}"
                   class="side-link {{ $active ? 'active' : '' }}">

                    {{ $icon }} &nbsp; {{ $label }}

                </a>

            @endif

        @endforeach

    @endforeach

    <div class="sidebar-label">Account</div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button class="side-link w-100 text-start border-0 bg-transparent"
                type="submit">
            ↪ &nbsp; Logout
        </button>
    </form>

</aside>

<div class="main-area">

    <header class="topbar d-flex justify-content-between align-items-center">

        <div>
            <div class="fw-bold">
                {{ $currentUser->company->name ?? 'Nova O2C' }}
            </div>

            <small class="text-muted">
                Order to Cash Management
            </small>
        </div>

        <div class="d-flex align-items-center gap-2">

            <div class="text-end d-none d-sm-block">

                <div class="fw-semibold small">
                    {{ $currentUser->name }}
                </div>

                <small class="text-muted">
                    {{ $currentUser->roles->pluck('name')->implode(', ') }}
                </small>

            </div>

            <span class="user-avatar">
                {{ strtoupper(substr($currentUser->name, 0, 1)) }}
            </span>

        </div>

    </header>

    <main class="content-area">
        @yield('content')
    </main>

</div>

</body>
</html>
