<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="{{ url('public/dashboard/plugins/font-awesome/css/font-awesome.min.css') . '?' . config('app.version') }}">
    <!-- Theme style -->
    <link rel="stylesheet"
        href="{{ url('public/dashboard/dist/css/adminlte.min.css') . '?' . config('app.version') }}">
    <!-- Date Picker -->
    <link rel="stylesheet"
        href="{{ url('public/dashboard/plugins/datepicker/datepicker3.css') . '?' . config('app.version') }}">
    <!-- Daterange picker -->
    <link rel="stylesheet"
        href="{{ url('public/dashboard/plugins/daterangepicker/daterangepicker-bs3.css') . '?' . config('app.version') }}">
    <!-- bootstrap wysihtml5 - text editor -->
    <link rel="stylesheet"
        href="{{ url('public/dashboard/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css') . '?' . config('app.version') }}">
    <!-- bootstrap rtl -->
    <link rel="stylesheet"
        href="{{ url('public/dashboard/dist/css/bootstrap-rtl.min.css') . '?' . config('app.version') }}">
    <!-- template rtl version -->
    <link rel="stylesheet"
        href="{{ url('public/dashboard/dist/css/custom-style.css') . '?' . config('app.version') }}">
    <link rel="stylesheet" href="{{ url('public/dashboard/dist/css/custom.css') . '?' . config('app.version') }}">

    <link rel="stylesheet" type="text/css"
        href="{{ url('public/dashboard/plugins/datatables/dataTables.bootstrap4.css') . '?' . config('app.version') }}" />
    <link rel="stylesheet" href="{{ url('public/dashboard/toastr/toastr.css') . '?' . config('app.version') }}">

    <link rel="stylesheet"
        href="{{ Url('public/plugins/persian-datepicker/persian-datepicker.css') . '?' . config('app.version') }}" />

    <link rel="stylesheet" href="{{ url('public/dashboard/plugins/select2/select2.min.css') }}">
    @yield('style')

    <script src="{{ url('public/dashboard/plugins/jquery/jquery.min.js') . '?' . config('app.version') }}"></script>
    <script src="{{ url('public/dashboard/plugins/datatables/jquery.dataTables.js') . '?' . config('app.version') }}">
    </script>
    <script src="{{ url('public/dashboard/plugins/datatables/dataTables.bootstrap4.js') . '?' . config('app.version') }}">
    </script>
    <script src="{{ url('public/dashboard/toastr/toastr.min.js') . '?' . config('app.version') }}"></script>

    <script src="{{ url('public/js/ajax.js') . '?' . config('app.version') }}"></script>
    <script src="{{ url('public/js/dataTable.js') . '?' . config('app.version') }}"></script>
    <link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
<script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>
    @yield('script_in_head')

</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        @include('layouts.header')

        @include('layouts.main-sidebar')
        <div class="content-wrapper">
            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>



        <footer class="main-footer">
            {{-- <strong> &copy; 2018 <a href="http://github.com/hesammousavi/">حسام موسوی</a>.</strong> --}}
        </footer>

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
    </div>


    <!-- Bootstrap 4 -->
    <script
        src="{{ url('public/dashboard/plugins/bootstrap/js/bootstrap.bundle.min.js') . '?' . config('app.version') }}">
    </script>
    <!-- AdminLTE App -->
    <script src="{{ url('public/dashboard/dist/js/adminlte.js') . '?' . config('app.version') }}"></script>

    {{-- <script src="{{ url('public/dashboard/plugins/html5-buttons/buttons.html5.min.js')  . '?' . config('app.version') }}"></script> --}}
    <script src="{{ Url('public/plugins/persian-datepicker/persian-date.js') . '?' . config('app.version') }}"></script>
    <script src="{{ Url('public/plugins/persian-datepicker/persian-datepicker.js') . '?' . config('app.version') }}">
    </script>

    <script src="{{ url('public/dashboard/plugins/select2/select2.full.min.js') }}"></script>


    <script>
        function initial_view() {
            $('.select2').select2();
            $('.select2').css('width', '100%')
            $(".persian-date").persianDatepicker({
                initialValue: false,
                viewMode: 'day',
                format: 'YYYY-MM-DD',
                initialValueType: 'persian',
                calendar: {
                    persian: {
                        leapYearMode: 'astronomical',
                        locale: 'fa'
                    }
                }
            });
        }
    </script>

    <script src="{{ url('public/js/loader.js') . '?' . config('app.version') }}"></script>
    <script src="{{ url('public/js/clearcach.js') . '?' . config('app.version') }}"></script>
    <script src="{{ url('public/js/scripts.js') . '?' . config('app.version') }}"></script>

    @yield('script')
    </div>



</body>

</html>
