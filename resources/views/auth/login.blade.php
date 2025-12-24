@extends('layouts.welcome')

@section('content')
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <img src="https://altfuel.ir/fa/public/logo.png" class="col-sm-12" alt="">
            </div>
            <div class="card-body">
                {{-- 🔔 اطلاعیه تماس --}}
                <div class="alert alert-success text-right" role="alert" style="font-size: 14px;">
                    در حال حاضر خطوط اصلی اتحادیه فعال هستند <br> <strong>02191013791</strong> <br> <strong>02191012961</strong>
                </div>
                {{-- <div class="alert alert-warning text-right" role="alert" style="font-size: 14px;">
                    در صورتی که موفق به تماس با خطوط اصلی نشدید، لطفاً با شماره پشتیبان <strong>02191012988</strong> تماس
                    بگیرید.
                </div> --}}
                <form action="javascript:void(0)" method="post" id="login-form">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="email" placeholder="موبایل">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fa fa-phone"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="رمز عبور">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fa fa-lock"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                            </div>
                        </div>
                    </div>
                </form>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary col-sm-12" onclick="submit()">ورود</button>
                </div>
                <hr>
                <div class="center-align" style="text-align: center">
                    <a href="{{ route('register') }}" class="text-center">صفحه ثبت نام</a>
                </div>
                <hr>
                <div class="center-align" style="text-align: center">
                    <a href="{{ route('password.request') }}" class="text-center">فراموشی رمز</a>
                </div>
            </div>
            <div class="card-body">
<a referrerpolicy='origin' target='_blank' href='https://trustseal.enamad.ir/?id=415037&Code=Ca929c8Fd94e346DE7ac52fb4D3ed935'><img referrerpolicy='origin' src='https://trustseal.enamad.ir/logo.aspx?id=415037&Code=Ca929c8Fd94e346DE7ac52fb4D3ed935' alt='' style='cursor:pointer' code='Ca929c8Fd94e346DE7ac52fb4D3ed935'></a>
            </div>

        </div>
    </div>
@endsection

@section('script')
    <script>
        function submit() {
            send_ajax_request(
                "{{ route('login') }}",
                $('#login-form').serialize(),
                function(response) {
                    show_message("به صفحه داشبورد منتقل میشوید")
                    window.location = "{{ url('admin') }}"
                },
                function(response) {
                    // console.log(response);
                    show_error(response)
                    hide_loading();
                }
            )
        }
    </script>
    <script>
        document.getElementById("login-form").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault(); // از ارسال فرم به صورت پیش‌فرض جلوگیری می‌کند
                document.querySelector("button[type='submit']").click(); // دکمه لاگین را شبیه‌سازی می‌کند
            }
        });
    </script>
@endsection
